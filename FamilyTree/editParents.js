/************************************************************************
 *  editParents.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page editParents.php.						*
 *									*
 *  History of editParents.js before merger of editMarriage.js:		*
 *	2010/09/05	make Close button work.				*
 *	2011/01/28	handle new object oriented feedback from	*
 *			editMarriage					*
 *	2011/03/26	support keyboard shortcuts			*
 *									*
 *  History of editParents.js as a separate file:			*
 *	2010/08/10	Change to use POST for invoking update scripts	*
 *	2010/08/13	Add function to create new spouse.		*
 *	2010/08/13	Add function to create new child.		*
 *	2010/08/13	Add function to detach existing child.		*
 *	2010/08/19	Correct invocation of editIndivid		*
 *	2010/08/21	Add function to reorder children by birth date.	*
 *	2010/08/27	Update page in detail on reorder rather than	*
 *			refresh, which wiped out changes made to other	*
 *			fields						*
 *	2010/08/28	Use getAttribute to get value of attribute of	*
 *			XML element					*
 *	2010/08/29	protect in case opener is terminated before	*
 *			this script					*
 *	2010/09/04	Use chooseIndivid.php for existing individuals	*
 *	2010/09/20	Do not overlay existing windows for		*
 *			editIndivid.php to permit creating multiple	*
 *			generations of parents without interruption	*
 *	2010/10/16	implement new format for maintaining citations	*
 *	2010/10/17	citation support moved to citTable.js		*
 *	2010/10/21	parameter removed from onclick methods of	*
 *			buttons						*
 *			use method=post to invoke updateMarriage.php	*
 *	2010/10/29	report explicit error message from		*
 *			detChildXml.php					*
 *			simplify and correct removal of child from web	*
 *			page						*
 *	2010/10/30	correct formatting of children after reordering	*
 *			add support for updating marriage status	*
 *	2010/11/11	set names of row and buttons on added child row	*
 *			so edit and detach child buttons work.		*
 *	2010/11/14	do not pass idir parameter to chooseIndivid	*
 *	2010/12/16	add method to permit editIndivid dialog to	*
 *			update table of children in this page		*
 *	2010/12/20	add detach spouse button			*
 *	2010/12/26	more object-oriented approach to notifying	*
 *			invoking page of the updated marriage by	*
 *			invoking a call-back method of the invoking	*
 *			document					*
 *	2011/01/13	check before calling function from invoking	*
 *			page that we are authorized to do so		*
 *	2011/02/06	standardize callbacks from editIndivid.php and	*
 *			chooseIndivid.php				*
 *	2011/02/21	fix callback calling sequence			*
 *			addChildToPage updates database to add child	*
 *	2011/02/23	new callbacks setNewFather and setNewWife with	*
 *			same parameters as changeChild & addChildToPage	*
 *	2011/02/26	initialize father's surname when invoking	*
 *			chooseIndivid.php to select existing child	*
 *	2011/03/03	row id not retrieved				*	
 *	2011/03/07	alert on error message from addChildXml		*
 *	2011/03/19	add keyboard shortcuts				*
 *	2011/03/25	set the initial focus on the "Update" button	*
 *			so pressing Enter closes the dialog, and the	*
 *			keyboard shortcuts work				*
 *	2011/04/22	syntax error on IE7				*
 *	2011/05/29	support button for editting pictures		*
 *	2011/06/09	support button for editting events		*
 *									*
 *  History of merged files:						*
 *	2011/06/11	functionality of editMarriage.js merged		*
 *	2011/08/21	always open editIndivid.php in a new window	*
 *			default surname of father to surname of child	*
 *	2011/08/24	if the individual has no existing parents	*
 *			display the menu to create the first set of	*
 *			parents						*
 *	2011/11/26	add try/catch on set onclick			*
 *			support database assisted location name		*
 *			Add buttons for editing Husband and Wife as	*
 *			individuals					*
 *			Support editing married surnames		*
 *	2012/01/13	change class names				*
 *			functionality shared with editMarriages.js moved*
 *			to commonMarriage.js				*
 *	2012/02/26	move addition of new family back here because	*
 *			it is different					*
 *	2013/01/24	dynamically build edit pane for family		*
 *	2013/03/12	changeLocation renamed to locationChanged	*
 *	2013/04/02	clear names to blank on detach			*
 *	2013/05/29	use actMouseOverHelp common function		*
 *	2013/06/11	explicitly set gender of new father		*
 *			request feedback on edit of father or mother	*
 *	2013/10/25	if the user modifies the name of the father or	*
 *			mother before clicking on the "Edit" button,	*
 *			pass the updated name				*
 *	2014/02/22	do not submit form if a modal dialog is shown	*
 *			<button>s now identified by id= not name=	*
 *			shared function addChild renamed to		*
 *			addExistingChild				*
 *	2014/02/27	do not submit form if a modal dialog is shown	*
 *			feedback method for editIndivid to update	*
 *			a child is made a method of the row containing	*
 *			the child, rather than the table of children	*
 *			Consolidate support for feedback from		*
 *			editIndivid.php by using the same style of	*
 *			feedback routine for any individual in the	*
 *			family						*
 *			validate and expand abbreviations in dates	*
 *	2014/03/18	support <fieldset>				*
 *	2014/07/16	better support for checking for open child	*
 *			windows						*
 *	2014/07/19	if not opened as a dialog go back to previous	*
 *			page instead of closing the window		*
 *	2014/10/10	positioning of child windows moved to		*
 *			commonMarriage.js				*
 *			change married surnames if husb surname changes	*
 *	2014/11/15	split element name into text and numeric parts	*
 *			to improve handling of elements in tables	*
 *			<button id='OrderEvents'> was not set up	*
 *	2014/11/16	initialization of page moved to PHP, do not	*
 *			invoke update at end of load.			*
 *	2014/11/28	enable onchange for child birth dates		*
 *	2015/02/02	disable and enable edit buttons when editing	*
 *			family member in <iframe>			*
 *	2015/02/10	open all child dialogs in left hand frame	*
 *	2015/02/20	use method addFamily from commonMarriages.js	*
 *			to implement addition of set of parents through	*
 *			PHP rather than through AJAX			*
 *	2015/02/23	track open windows for spouse or child to	*
 *			prevent updating family while open		*
 *	2015/02/26	do not overwrite child when editing husb or wife*
 *			bad row id passed to chooseIndivid.php		*
 *	2015/06/08	disable edit child buttons on createMother and	*
 *			createFather					*
 *			invoke create if editFather/editMother and	*
 *			parent is not yet defined			*
 *	2015/06/19	enter key on input fields in child description	*
 *			adds another child				*
 *			validate birth and death date of child		*
 *	2015/08/12	add support for tree divisions of database	*
 *	2016/02/06	call pageInit					*
 *	2016/05/31	use common function dateChanged			*
 *	2016/06/23	chooseMother didn't work			*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/

window.onload	= loadEdit;

/************************************************************************
 *  loadEdit								*
 *									*
 *  Initialize dynamic behavior of page					*
 ************************************************************************/
function loadEdit()
{
    pageInit();

    // get parameters from URL search string
    var	editPref	= null;
    var	idmrNotSet	= true;

    if (typeof(args.idmr) == "string")
    {			// idmr parameter passed
	editPref	= document.indForm.elements['Edit' + args.idmr];
	if (editPref)
	{
	    idmrNotSet	= false;
	}
    }			// idmr parameter passed
   
    // handle keystrokes that apply to the entire dialog
    document.body.onkeydown	= epKeyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
	var form	= document.forms[fi];
	if (form.name == 'indForm')
	{	// list of marriages form
	    // set action methods for form
	    form.onsubmit	= validateForm;
	    form.onreset 	= resetForm;
	}	// list of marriages form
	else
	if (form.name == 'famForm')
	{	// individual marriage form
	    // set action methods for form
	    form.onsubmit	= validateForm;
	    form.onreset 	= resetForm;

	    // callback from editEvent.php
	    form.eventFeedback	= eventFeedback;
	}	// individual marriage form

	var formElts	= form.elements;
	for (var i = 0; i < formElts.length; ++i)
	{	// loop through all elements
	    var element	= formElts[i];

	    if (element.nodeName.toUpperCase() == 'FIELDSET')
		continue;

	    var	name;
	    if (element.name && element.name.length > 0)
		name	= element.name;
	    else
		name	= element.id;

	    // default handling of <input> elements
	    element.onkeydown	= keyDown;
	    element.onchange	= change;	// default handler

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);
    
	    // do element specific initialization
	    var namePattern	= /^([a-zA-Z$_]+)([0-9]*)$/;
	    var pieces		= namePattern.exec(name);
	    if (pieces === null)
	    {
		alert("editMarriages.js: onLoad: unable to parse name='" +
			name + "'");
		pieces	= [name, name, ''];
	    }
	    var	colName		= pieces[1];
	    var rowId		= pieces[2];

	    switch(colName)
	    {
		case 'Add':
		{	// add a marriage
		    element.onclick	= addFamily;
		    break;
		}	// Add
    
		case 'Finish':
		{	// close button
		    element.onclick	= finish;
		    // put the initial keyboard focus on the "Close" button so
		    // pressing Enter closes the dialog
		    element.focus();
		    break;
		}	// Finish
    
		case 'Reorder':
		{	// reorder marriages by date
		    element.onclick	= marrReorder;
		    break;
		}	// Reorder

		case 'HusbSurname':
		{	// surname fields
		    element.checkfunc		= checkName;
		    element.onchange		= changeHusbSurname;
		    break;
		}	// surname fields

		case 'WifeSurname':
		{	// surname fields
		    element.checkfunc		= checkName;
		    element.onchange		= change;
		    break;
		}	// surname fields

		case 'HusbGivenName':
		case 'WifeGivenName':
		{	// name fields
		    element.checkfunc		= checkName;
		    element.onchange		= change;
		    break;
		}	// name fields

		case 'MarD':
		{		// marriage date
		    element.abbrTbl		= MonthAbbrs;
		    element.onchange		= dateChanged;
		    element.checkfunc		= checkDate;
		    break;
		}		// marriage date

		case 'MarLoc':
		{		// marriage location
		    element.abbrTbl		= evtLocAbbrs;
		    element.onchange		= locationChanged;
		    break;
		}		// marriage location

		case 'editHusb':
		{
		    editChildButtons.push(element);
		    element.onclick		= editFather;
		    break;
		}

		case 'chooseHusb':
		{
		    element.onclick		= chooseFather;
		    break;
		}

		case 'createHusb':
		{
		    element.onclick		= createFather;
		    break;
		}

		case 'detachHusb':
		{
		    element.onclick		= detachHusb;
		    break;
		}

		case 'editWife':
		{
		    editChildButtons.push(element);
		    element.onclick		= editMother;
		    break;
		}

		case 'chooseWife':
		{
		    element.onclick		= chooseMother;
		    break;
		}

		case 'createWife':
		{
		    element.onclick		= createMother;
		    break;
		}

		case 'detachWife':
		{
		    element.onclick		= detachWife;
		    break;
		}

		case 'marriageDetails':
		{
		    element.onclick		= marriageDetails;
		    break;
		}

		case 'sealingDetails':
		{
		    element.onclick		= sealingDetails;
		    break;
		}

		case 'noteDetails':
		{
		    element.onclick		= noteDetails;
		    break;
		}

		case 'noChildren':
		{
		    element.onclick		= noChildren;
		    break;
		}

		case 'noChildDetails':
		{
		    element.onclick		= noChildDetails;
		    break;
		}

		case 'AddEvent':
		{
		    element.onclick		= addEvent;
		    break;
		}

		case 'OrderEvents':
		{		// order events by date
		    element.onclick		= orderEvents;
		    break;
		}		// order events by date

		case 'addChild':
		{
		    element.onclick		= addExistingChild;
		    editChildButtons.push(element);
		    break;
		}

		case 'addNewChild':
		{
		    element.onclick		= addNewChild;
		    editChildButtons.push(element);
		    break;
		}

		case 'update':
		{
		    element.onclick		= updateMarr;
		    break;
		}

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
		    element.onchange	= changeNameRule;
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
		    element.onkeydown		= childKeyDown;
		    element.onchange		= changeCBirth;
		    element.checkfunc		= checkDate;
		    element.checkfunc();
		    break;
		}		// birth date of a child

		case 'Cdeath':
		{		// death date of a child
		    element.onkeydown		= childKeyDown;
		    element.onchange		= change;
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

	    }	// switch on element name
	}	// loop through all elements in the form
    }		// loop through all forms

    // provide methods for other pages to modify information on husband
    // and wife
    var	husbRow			= document.getElementById('Husb');
    husbRow.changePerson	= changeHusb;
    var	wifeRow			= document.getElementById('Wife');
    wifeRow.changePerson	= changeWife;

    // provide methods for other pages to add a child onto this page
    // and to change an existing child
    var	childTable		= document.getElementById('children');
    childTable.addChildToPage	= addChildToPage;

    // process the nodes immediately under the table of children
    // define the changePerson feedback method for each node which
    // contains information about a child
    for(var subElement = childTable.firstChild;
	subElement;
	subElement = subElement.nextSibling)
    {
	if (subElement.nodeName)
	{		// a node element
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
	}		// element has a nodeName attribute
    }			// loop through all immediate children

}		// loadEdit

/************************************************************************
 *  validateForm							*
 *									*
 *  Ensure that the data entered by the user has been minimally		*
 *  validated before submitting the form.				*
 *									*
 *  Input:								*
 *	this		<form>						*
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
}		// validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 *									*
 *  Input:								*
 *	this		<form>						*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  finish								*
 *									*
 *  This method is called when the user requests to close		*
 *  the window.								*
 ************************************************************************/
function finish()
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
}	// finish

/************************************************************************
 *  epKeyDown								*
 *									*
 *  The key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Citation button.						*
 *									*
 *  Parameters:								*
 *	e	W3C compliant browsers pass an event as a parameter	*
 ************************************************************************/
function epKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
	e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.indForm;

    if (e.ctrlKey)
    {		// ctrl
	if (code == 83)
	{	// ctrl-S
	    window.close();
	    return false;	// do not perform default action
	}	// ctrl-S
    }		// ctrl

    if (e.altKey)
    {		// alt
	// take action based upon code
	switch (code)
	{	// switch on key code
	    case 65:
	    {		// alt-A
		form.Add.onclick();
		return false;
	    }		// alt-A

	    case 67:
	    {		// alt-C
		window.close();
		return false;
	    }		// alt-C

	}	// switch on key code
    }		// alt

    return true;
}		// epKeyDown

/************************************************************************
 *  editFather								*
 *									*
 *  Prompt the user to edit details of the father			*
 *  in the marriage.							*
 *									*
 *  Input:								*
 *	this	<button name='editHusb'>				* 
 ************************************************************************/
function editFather()
{
    var	form		= this.form;
    var idir		= form.IDIRHusb.value;
    if (idir > 0)
    {		// father present
	for (var ib = 0; ib < editChildButtons.length; ib++)
	{			// disable all editChild buttons
	    editChildButtons[ib].disabled	= true;
	}			// disable all editChild buttons
	var script	= "editIndivid.php?idir=" + idir + "&rowid=Husb" +
	  "&initGivenName=" + encodeURIComponent(form.HusbGivenName.value) + 
	  "&initSurname=" + encodeURIComponent(form.HusbSurname.value) +
	  '&treeName=' + encodeURIComponent(form.treename.value);
	var childWindow	= openFrame("husbFrame",
				    script,
				    "left");
	childWindows.push(childWindow);
    }		// father present
    else
	document.getElementById('createHusb').onclick();
}		// editFather

/************************************************************************
 *  chooseFather							*
 *									*
 *  Prompt the user to select an existing individual as husband		*
 *  in the marriage.							*
 *									*
 *  Input:								*
 *	this	<button name='chooseHusb'>				* 
 ************************************************************************/
function chooseFather()
{
    var	form		= this.form;
    var surname		= form.HusbSurname.value;
    var url		= "chooseIndivid.php?id=Husb" + 
	  "&name=" + encodeURIComponent(surname) +
	  '&treeName=' + encodeURIComponent(form.treename.value);
    var childWindow	= openFrame("chooserFrame",
				    url,
				    "left");
}		// chooseFather

/************************************************************************
 *  editMother								*
 *									*
 *  Prompt the user to edit details of the mother in the marriage.	*
 *									*
 *  Input:								*
 *	this	<button name='editWife'>				*
 ************************************************************************/
function editMother()
{
    var	form	= this.form;
    var idir	= form.IDIRWife.value;
    if (idir > 0)
    {		// mother present
	for (var ib = 0; ib < editChildButtons.length; ib++)
	{			// disable all editChild buttons
	    editChildButtons[ib].disabled	= true;
	}			// disable all editChild buttons
	var script	= "editIndivid.php?idir=" + idir + "&rowid=Wife" +
	  "&initGivenName=" + encodeURIComponent(form.WifeGivenName.value) + 
	  "&initSurname=" + encodeURIComponent(form.WifeSurname.value) +
	  '&treeName=' + encodeURIComponent(form.treename.value);
	var childWindow	= openFrame("wifeFrame",
				    script,
				    "left");
	childWindows.push(childWindow);
    }		// wife present
    else
	document.getElementById('createWife').onclick();
}		// editMother

/************************************************************************
 *  chooseMother							*
 *									*
 *  Prompt the user to select an existing individual as wife		*
 *  in the marriage.							*
 *									*
 *  Input:								*
 *	this	<button name='chooseWife'>				*
 ************************************************************************/
function chooseMother()
{
    form		= this.form;
    var surname		= form.WifeSurname.value;
    var url		= "chooseIndivid.php?id=Wife" +
	  "&name=" + encodeURIComponent(surname) +
	  '&treeName=' + encodeURIComponent(form.treename.value);
    var childWindow	= openFrame("chooserFrame",
				    url,
				    "left");
}		// chooseMother

/************************************************************************
 *  createFather							*
 *									*
 *  This method is called when the user requests to add 		*
 *  a new individual to the set of parents as husband			*
 *									*
 *  Input:								*
 *	this	<button id='createHusb'>				*
 ************************************************************************/
function createFather()
{
    var	form		= this.form;
    var script		= "editIndivid.php?rowid=Husb&initGender=0" +
	  "&initGivenName=" + encodeURIComponent(form.HusbGivenName.value) + 
	  "&initSurname=" + encodeURIComponent(form.HusbSurname.value) + 
	  '&treeName=' + encodeURIComponent(form.treename.value);
    var childWindow	= openFrame("husbFrame",
				    script,
				    "left");
    childWindows.push(childWindow);
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {				// disable all editChild buttons
	editChildButtons[ib].disabled	= true;
    }				// disable all editChild buttons
}	// createFather

/************************************************************************
 *  createMother							*
 *									*
 *  This method is called when the user requests to add 		*
 *  a new individual to the set of parents as wife.			*
 *									*
 *  Input:								*
 *	this	<button id='createWife'>				*
 ************************************************************************/
function createMother()
{
    var	form		= this.form;
    var script		= "editIndivid.php?rowid=Wife&initGender=1" +
	  "&initGivenName=" + encodeURIComponent(form.WifeGivenName.value) + 
	  "&initSurname=" + encodeURIComponent(form.WifeSurname.value) + 
	  '&treeName=' + encodeURIComponent(form.treename.value);

    var childWindow	= openFrame("wifeFrame",
				    script,
				    "left");
    childWindows.push(childWindow);
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {				// disable all editChild buttons
	editChildButtons[ib].disabled	= true;
    }				// disable all editChild buttons
}	// createMother

/************************************************************************
 *  em1KeyDown								*
 *									*
 *  Handle key strokes that apply to the dialog as a whole.  For example*
 *  the key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Marriage button.						*
 *									*
 *  Parameters:								*
 *	e	W3C compliant browsers pass an event as a parameter	*
 ************************************************************************/
function em1KeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
	e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.famForm;

    if (e.ctrlKey)
    {		// ctrl
	if (code == 83)
	{		// letter 'S'
	    var	button	= form.update;
	    button.onclick();
	    return false;	// do not perform standard action
	}		// letter 'S'
    }		// ctrl

    // handle alt-Key combinations
    if (e.altKey)
    {		// alt
	var	button	= null;

	// take action based upon code
	switch (code)
	{
	    case 65:
	    {		// letter 'A'
		button	= document.getElementById('addCitation');
		button.onclick();
		return false;
	    }		// letter 'A'
    
	    case 69:
	    {		// letter 'E'
		button	= document.getElementById('addChild');
		button.onclick();
		return false;
	    }		// letter 'E'
    
	    case 72:
	    {		// letter 'F'
		button	= document.getElementById('createHusb');
		button.onclick();
		return false;
	    }		// letter 'F'
    
	    case 77:
	    {		// letter 'M'
		button	= document.getElementById('createWife');
		button.onclick();
		return false;
	    }		// letter 'M'
    
	    case 78:
	    {		// letter 'N'
		button	= document.getElementById('addNewChild');
		button.onclick();
		return false;
	    }		// letter 'N'
    
	    case 79:
	    {		// letter 'O'
		button	= document.getElementById('orderChildren');
		button.onclick();
		return false;
	    }		// letter 'O'

	    case 80:
	    {		// letter 'P'
		button	= document.getElementById('Pictures');
		button.onclick();
		return false;
	    }		// letter 'P'
    
	    case 85:
	    {		// letter 'U'
		button	= document.getElementById('update');
		button.onclick();
		return false;
	    }		// letter 'U'
    
	    case 86:
	    {		// letter 'V'
		button	= document.getElementById('Events');
		button.onclick();
		return false;
	    }		// letter 'V'

	    default:
	    {
		alert("alt-" + code);
		return true;
	    } 
	}	    // switch on key code
    }		// alt key held down

    return true;
}		// em1KeyDown

