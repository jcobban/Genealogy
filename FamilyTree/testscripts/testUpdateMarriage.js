/**
 *  editMarriages.js
 *
 *  Javascript code to implement dynamic functionality of the
 *  page editMarriages.php.
 *
 *  History of editMarriages.js before merger of editMarriage.js:
 *		2010/11/08		improve error handling on delete marriage
 *		2010/12/04		improve separation of HTML & JS
 *		2010/12/26		add callback method marriageUpdated to
 *						receive notification from script editMarriage.php
 *		2011/02/21		do not fail if calling page has already closed
 *		2011/03/03		support keyboard shortcuts:
 *						ctrl-S and alt-C for close
 *						alt-A for add marriage
 *		2011/04/10		reduce size of window for editMarriage.php
 *
 *  History of editMarriage.js as a separate file:		
 *		2010/08/10		Change to use POST for invoking update scripts
 *		2010/08/13		Add function to create new spouse.
 *		2010/08/13		Add function to create new child.
 *		2010/08/13		Add function to detach existing child.
 *		2010/08/19		Correct invocation of editIndivid
 *		2010/08/21		Add function to reorder children by birth date.
 *		2010/08/27		Update page in detail on reorder rather than
 *						refresh, which wiped out changes made to other fields
 *		2010/08/28		Use getAttribute to get value of attribute of XML element
 *		2010/08/29		protect in case opener is terminated before this script
 *		2010/09/04		Use chooseIndivid.php for existing individuals
 *		2010/09/20		Do not overlay existing windows for editIndivid.php
 *						to permit creating multiple generations of parents
 *						without interruption
 *		2010/10/16		implement new format for maintaining citations
 *		2010/10/17		citation support moved to citTable.js
 *		2010/10/21		parameter removed from onclick methods of buttons
 *						use method=post to invoke updateMarriage.php
 *		2010/10/29		report explicit error message from detChildXml.php
 *						simplify and correct removal of child from web page
 *		2010/10/30		correct formatting of children after reordering
 *						add support for updating marriage status
 *		2010/11/11		set names of row and buttons on added child row
 *						so edit and detach child buttons work.
 *		2010/11/14		do not pass idir parameter to chooseIndivid
 *		2010/12/16		add method to permit editIndivid dialog to update
 *						table of children in this page
 *		2010/12/20		add detach spouse button
 *		2010/12/26		more object-oriented approach to notifying
 *						invoking page of the updated marriage by
 *						invoking a call-back method of the invoking document.
 *		2011/01/13		check before calling function from invoking page
 *		2011/02/06		standardize callbacks from editIndivid.php and
 *						chooseIndivid.php
 *		2011/02/21		fix callback calling sequence
 *						addChildToPage updates database to add		 child
 *		2011/02/23		new callbacks setNewHusb and setNewWife with
 *						same parameters as changeChild & addChildToPage
 *		2011/02/26		initialize father's surname when invoking
 *						chooseIndivid.php to select existing child
 *		2011/03/03		row id not retrieved
 *		2011/03/07		alert on error message from addChildXml
 *		2011/03/19		add keyboard shortcuts
 *		2011/03/25		set the initial focus on the "Update" button
 *						so pressing Enter closes the dialog, and the
 *						keyboard shortcuts work
 *		2011/04/22		syntax error on IE7
 *		2011/05/29		support button for editting pictures
 *		2011/06/09		support button for editting events
 *
 *  History of merged files:
 *		2011/06/11		functionality of editMarriage.js merged
 *		2011/06/24		add gender parameter to changeChild callback
 *		2011/07/14		enlarge popup windows
 *		2011/07/29		explicitly pass updated values of date and location
 *						to editEvent.php
 *		2011/08/21		always open editIndivid.php in a new window
 *		2011/08/22		if the individual has no existing families
 *						display the menu to create the first family
 *		2011/09/18		add try/catch on set onclick
 *		2011/10/01		support database assisted location name
 *		2011/11/15		add keyword idmr to initiate edit of pre-selected
 *						family.
 *						Add buttons for editing Husband and Wife as individuals
 *		2011/11/26		Support editing married surnames
 *		2012/01/07		explicitly pass parents names to child creation
 *		2012/01/11		do not use refresh to update the list of marriages
 *						because that causes the displayed marriage to be
 *						refreshed as well, removing any changes made since
 *						the last write to the database.
 *						If the name of the husband is changed change the now
 *						exposed married surnames of the husband and, if
 *						requires by the marriage name rule, the wife
 *		2012/01/13		change class names
 *						most functionality moved to commonMarriage.js
 *		2012/04/21		align child windows with main window
 *		2012/10/13		enable edit buttons for spouses
 *		2012/11/18		change edit and delete event buttons so the name
 *						of the button contains the event type for internal
 *						events and the IDER for LegacyEvent instances
 *		2013/01/17		correct comments
 *		2019/05/19      call element.click to trigger button click      *
 *
 *  Copyright &copy; 2019 James A. Cobban
 **/

window.onload	= loadEdit;

/**
 *  loadEdit
 *
 *  Initialize elements once the page is loaded into the browser.
 **/
function loadEdit()
{
    // the edit button for the preferred marriage
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
    document.body.onkeydown	= emKeyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		form	= document.forms[fi];
		if (form.name == 'indForm')
		{	// list of marriages form
		    // set action methods for form
		    form.onsubmit		= validateForm;
		    form.onreset 		= resetForm;
		}	// list of marriages form
		else
		if (form.name == 'famForm')
		{	// individual marriage form
		    // set action methods for form
		    form.onsubmit	= validateForm;
		    form.onreset 	= resetForm;

		    // callback from editIndivid
		    form.changeSpouse	= changeSpouse;

		    // callback from editEvent
		    form.eventFeedback	= eventFeedback;
		}	// individual marriage form

		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{	// loop through all elements
		    var elt	= formElts[i];
		    var	name;
		    if (elt.name && elt.name.length > 0)
				name	= elt.name;
		    else
				name	= elt.id;
		    elt.onkeydown	= keyDown;
		    elt.onchange	= change;	// default handler

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (elt.parentNode.nodeName == 'TD')
		    {		// set mouseover on containing cell
				elt.parentNode.onmouseover	= eltMouseOver;
				elt.parentNode.onmouseout	= eltMouseOut;
		    }		// set mouseover on containing cell
		    else
		    {		// set mouseover on input element itself
				elt.onmouseover		= eltMouseOver;
				elt.onmouseout		= eltMouseOut;
		    }		// set mouseover on input element itself
    
		    // do element specific initialization
		    switch(name)
		    {
				case 'Add':
				{	// add a marriage
				    elt.onclick	= marrAdd;
				    break;
				}	// Add
    
				case 'Finish':
				{	// close button
				    elt.onclick	= finish;
				    // put the initial keyboard focus on the "Close" button so
				    // pressing Enter closes the dialog
				    elt.focus();
				    break;
				}	// Finish
    
				case 'Reorder':
				{	// reorder marriages by date
				    elt.onclick	= marrReorder;
				    break;
				}	// Reorder

				case 'MarLoc':
				{		// marriage location
				    elt.abbrTbl		= evtLocAbbrs;
				    elt.onchange	= changeLocation;
				    break;
				}		// marriage location

				case 'editHusb':
				{
				    elt.onclick		= editHusb;
				    break;
				}

				case 'changeHusb':
				{
				    elt.onclick		= changeHusb;
				    break;
				}

				case 'createHusb':
				{
				    elt.onclick		= createHusb;
				    break;
				}

				case 'detachHusb':
				{
				    elt.onclick		= detachHusb;
				    break;
				}

				case 'editWife':
				{
				    elt.onclick		= editWife;
				    break;
				}

				case 'changeWife':
				{
				    elt.onclick		= changeWife;
				    break;
				}

				case 'createWife':
				{
				    elt.onclick		= createWife;
				    break;
				}

				case 'detachWife':
				{
				    elt.onclick		= detachWife;
				    break;
				}

				case 'marriageDetails':
				{
				    elt.onclick		= marriageDetails;
				    break;
				}

				case 'noteDetails':
				{
				    elt.onclick		= noteDetails;
				    break;
				}

				case 'AddEvent':
				{
				    elt.onclick		= addEvent;
				    break;
				}

				case 'addChild':
				{
				    elt.onclick		= addChild;
				    break;
				}

				case 'addNewChild':
				{
				    elt.onclick		= addNewChild;
				    break;
				}

				case 'update':
				{
				    elt.onclick		= updateMarr;
				    break;
				}

				case 'orderChildren':
				{
				    elt.onclick		= orderChildren;
				    break;
				}

				case 'Pictures':
				{
				    elt.onclick		= editPictures;
				    break;
				}

				case 'MarriedNameRule':
				{
				    elt.onchange	= changeNameRule;
				    break;
				}

				default:
				{
				    if (name.substring(0,10) == 'EditIEvent')
					elt.onclick	= editIEvent;
				    else
				    if (name.substring(0,9) == 'DelIEvent')
					elt.onclick	= delIEvent;
				    else
				    if (name.substring(0,9) == 'EditEvent')
					elt.onclick	= editEvent;
				    else
				    if (name.substring(0,8) == 'DelEvent')
					elt.onclick	= delEvent;
				    else
				    if (name.substring(0,9) == 'editChild')
					elt.onclick	= editChild;
				    else
				    if (name.substring(0,8) == 'detChild')
					elt.onclick	= detChild;
				    else
				    if (name.substring(0,4) == 'Pref')
					elt.onclick	= clickPref;
				    else
				    if (name.substring(0,4) == 'Edit')
				    {
					elt.onclick	= marrEdit;
					var prefName	= 'Pref' + name.substring(4);
					var prefbox	= form.elements[prefName];
					if (editPref === null ||
					    (idmrNotSet && prefbox.checked))
					    editPref	= elt;
				    }
				    else
				    if (name.substring(0,6) == 'Delete')
					elt.onclick	= marrDel;
				}	// other elements
		    }	// switch on element name
		}	// loop through all elements in the form
    }		// loop through all forms

    // provide methods for other pages to modify information on husband
    // and wife
    var	husbRow		= document.getElementById('Husb');
    husbRow.setNew	= setNewHusb;
    var	wifeRow		= document.getElementById('Wife');
    wifeRow.setNew	= setNewWife;

    // provide methods for other pages to add a child onto this page
    // and to change an existing child
    var	childTable	= document.getElementById('children');
    childTable.addChildToPage	= addChildToPage;
    childTable.changeChild	= changeChild;

    // if this dialog is activated and there are no existing marriages
    // for the current individual, then display the dialog to add the first
    // set of parents, otherwise edit the preferred set of parents
    if (editPref)
    {
		editPref.click();
    }
    else
    {
		document.getElementById('Add').click();
    }
		
}		// loadEdit

/**
 *  validateForm
 *
 *  Ensure that the data entered by the user has been minimally validated
 *  before submitting the form.
 **/
function validateForm()
{
    return true;
}		// validateForm

/**
 *  resetForm
 *
 *  This method is called when the user requests the form
 *  to be reset to default values.
 **/
function resetForm()
{
    return true;
}	// resetForm

/**
 *  finish
 *
 *  This method is called when the user requests to close
 *  the window.
 **/
function finish()
{
    window.close();
    return true;
}	// finish

/**
 *  emKeyDown
 *
 *  Handle key strokes that apply to the dialog as a whole.  For example
 *  the key combinations Ctrl-S and Alt-C are interpreted to close the
 *  dialog, as shortcut alternatives to using the mouse to click the 
 *  Close button.
 *
 *  Parameters:
 *		e		W3C compliant browsers pass an event as a parameter
 **/
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
}		// emKeyDown

/**
 *  editHusb
 *
 *  Prompt the user to edit details of the existing husband
 *  in the marriage.
 **/
function editHusb()
{
    var	form	= document.famForm;
    var idir	= form.IDIRHusb.value;
    if (idir > 0)
    {		// husband present
		var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					indivParms;
		window.open("/FamilyTree/editIndivid.php?idir=" + idir, 
				    "individ",
				    config);
    }		// husband present
}		// editHusb

/**
 *  changeHusb
 *
 *  Prompt the user to select an existing individual as husband
 *  in the marriage.
 **/
function changeHusb()
{
    var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					editIndivParms;
    window.open("/FamilyTree/chooseIndivid.php?id=Husb", 
				"chooser",
				config);
 
}		// changeHusb

/**
 *  setNewHusb
 *
 *  Callback from chooseIndivid.php or editIndivid.php 
 *  for setting identification of husband.
 *
 *  Parameters:
 *		this				table row element
 *		idir				IDIR of husband
 *		newGiven		replacement given name
 *		newSurname		replacement surname
 *		newBirth		replacement birth date
 *		newDeath		replacement death date
 **/
function setNewHusb(idir,
				    newGiven, 
				    newSurname, 
				    newBirth, 
				    newDeath)
{
    var	form			= document.famForm;
    form.IDIRHusb.value		= idir;
    form.HusbGivenName.value	= newGiven;
    form.HusbSurname.value	= newSurname;
    form.HusbMarrSurname.value	= newSurname;
    form.editHusb.disabled	= false;
    if (form.MarriedNameRule.value == '1')
		form.WifeMarrSurname.value	= newSurname;
}		// setNewHusb

/**
 *  setNewWife
 *
 *  Callback from chooseIndivid.php or editIndivid.php 
 *  for setting identification of wife.
 *
 *  Parameters:
 *		this				table row element
 *		idir				IDIR of wife
 *		newGiven		replacement given name
 *		newSurname		replacement surname
 *		newBirth		replacement birth date
 *		newDeath		replacement death date
 **/
function setNewWife(idir,
				    newGiven, 
				    newSurname, 
				    newBirth, 
				    newDeath)
{
    var	form	= document.famForm;
    form.IDIRWife.value		= idir;
    form.WifeGivenName.value	= newGiven;
    form.WifeSurname.value	= newSurname;
    form.editWife.disabled	= false;
}		// setNewWife

/**
 *  gotHusb
 *
 *  This method is called when the XML file representing
 *  information on the selected husband is retrieved.
 *
 *  Parameters:
 *		xmlDoc				response as an XML document
 **/
function gotHusb(xmlDoc)
{
    var	famForm	= document.famForm;

    // get values from the XML file
    var newIdir	= xmlDoc.getElementsByTagName("idir");
    if (newIdir === null)
		alert("editMarriages.js: gotHusb: newIdir is null");
    newIdir		= newIdir[0].textContent;
    famForm.IDIRHusb.value	= newIdir;
    famForm.editHusb.disabled	= false;
    var newGiven	= xmlDoc.getElementsByTagName("givenname");
    if (newGiven === null)
		alert("editMarriages.js: gotHusb: newGiven is null");
    newGiven		= newGiven[0].textContent;
    famForm.HusbGivenName.value	= newGiven;
    var newSurname	= xmlDoc.getElementsByTagName("surname");
    if (newSurname === null)
		alert("editMarriages.js: gotHusb: newSurname is null");
    newSurname		= newSurname[0].textContent;
    famForm.HusbSurname.value	= newSurname;
}

/**
 *  noHusb
 *
 *  This method is called if there is no husband
 *  file.
 **/
function noHusb()
{
    alert ('editMarriages.js: noHusb: Missing XML file response for selecting existing husband.');
}		// noHusb

/**
 *  detachHusb
 *
 *  This method is called when the user requests that the current
 *  husband be detached with no replacement.
 **/
function detachHusb()
{
    var	famForm			= this.form;

    famForm.IDIRHusb.value	= 0;
    famForm.HusbGivenName.value	= '?';
    famForm.HusbSurname.value	= '?';
    famForm.detachHusb.disabled	= true;
    famForm.editHusb.disabled	= true;
}		// detachHusb

/**
 *  editWife
 *
 *  Prompt the user to edit details of an existing wife
 *  in the marriage.
 **/
function editWife()
{
    var	form	= document.famForm;
    var idir	= form.IDIRWife.value;
    if (idir > 0)
    {		// wife present
		var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					indivParms;
		window.open("/FamilyTree/editIndivid.php?idir=" + idir, 
				    "individ",
				    config);
    }		// wife present
}		// editWife

/**
 *  changeWife
 *
 *  Prompt the user to set the wife by selecting from a list of
 *  existing women in the database.
 **/
function changeWife()
{
    var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					editIndivParms;
    window.open("/FamilyTree/chooseIndivid.php?id=Wife", 
				"chooser",
				config);
}		// changeWife

/**
 *  gotWife
 *
 *  This method is called when the XML file representing
 *  information on the new wife is retrieved.
 *
 *  Parameters:
 *		xmlDoc				response as an XML document
 **/
function gotWife(xmlDoc)
{
    var	famForm	= document.famForm;

    // get the individual info from the XML file
    var newIdir	= xmlDoc.getElementsByTagName("idir");
    if (newIdir === null)
		alert("editMarriages.js: gotWife: newIdir is null");
    newIdir		= newIdir[0].textContent;
    famForm.IDIRWife.value	= newIdir;
    famForm.editWife.disabled	= false;
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

/**
 *  noWife
 *
 *  This method is called if there is no wife response
 *  file.
 **/
function noWife()
{
    alert ('Missing XML file response for selecting existing wife.');
}		// noWife

/**
 *  detachWife
 *
 *  This method is called when the user requests that the current
 *  wife be detached with no replacement.
 **/
function detachWife()
{
    var	famForm			= this.form;

    famForm.IDIRWife.value	= 0;
    famForm.WifeGivenName.value	= '?';
    famForm.WifeSurname.value	= '?';
    famForm.detachWife.disabled	= true;
    famForm.editWife.disabled	= true;
}		// detachWife

/**
 *		createHusb
 *
 *		This method is called when the user requests to add 
 *		a new individual to the marriage as husband
 **/

function createHusb()
{
    var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					indivParms;
    window.open('/FamilyTree/editIndivid.php?rowid=Husb',
				"_blank",
				config);
}	// createHusb

/**
 *  createWife
 *
 *  This method is called when the user requests to add 
 *  a new individual to the marriage as wife.
 **/
function createWife()
{
    var	config	= "top=" + window.screenY +
					",left=" + window.screenX + "," +
					indivParms;
    window.open('/FamilyTree/editIndivid.php?rowid=Wife&initGender=1',
				"_blank",
				config);
}	// createWife

/**
 *  em1KeyDown
 *
 *  Handle key strokes that apply to the dialog as a whole.  For example
 *  the key combinations Ctrl-S and Alt-U are interpreted to apply the
 *  update, as shortcut alternatives to using the mouse to click the 
 *  Update Marriage button.
 *
 *  Parameters:
 *		e		W3C compliant browsers pass an event as a parameter
 **/
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
		    button.click();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }		// ctrl

    // handle alt-Key combinations
    if (e.altKey)
    {		// alt
		// take action based upon code
		switch (code)
		{
		    case 65:
		    {		// letter 'A'
				var	button	= document.getElementById('addCitation');
				button.click();
				return false;
		    }		// letter 'A'
    
		    case 69:
		    {		// letter 'E'
				var	button	= form.addChild;
				button.click();
				return false;
		    }		// letter 'E'
    
		    case 72:
		    {		// letter 'H'
				var	button	= form.createHusb;
				button.click();
				return false;
		    }		// letter 'H'
    
		    case 78:
		    {		// letter 'N'
				var	button	= form.addNewChild;
				button.click();
				return false;
		    }		// letter 'N'
    
		    case 79:
		    {		// letter 'O'
				var	button	= form.orderChildren;
				button.click();
				return false;
		    }		// letter 'O'

		    case 80:
		    {		// letter 'P'
				form.Pictures.click();
				return false;
		    }		// letter 'P'
    
		    case 85:
		    {		// letter 'U'
				var	button	= form.update;
				button.click();
				return false;
		    }		// letter 'U'
    
		    case 86:
		    {		// letter 'V'
				var	button	= form.Events;
				button.click();
				return false;
		    }		// letter 'V'
    
		    case 87:
		    {		// letter 'W'
				var	button	= form.createWife;
				button.click();
				return false;
		    }		// letter 'N'

		    default:
		    {
				//alert("alt-" + code);
				return true;
		    } 
		}	    // switch on key code
    }		// alt key held down

    return true;
}		// em1KeyDown



