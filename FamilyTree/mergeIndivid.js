/************************************************************************
 *  mergeIndivid.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page mergeIndivid.php.												*
 *																		*
 *  History:															*
 *		2010/12/25		created											*
 *		2012/01/13		change class names								*
 *		2013/01/28		gender removed from page because implementation	*
 *						now guarantees that it matches					*
 *		2013/01/29		support Do Not Merge table for individuals		*
 *						add support for mouse-over help					*
 *		2013/05/29		use actMouseOverHelp common function			*
 *						standardize object initialization				*
 *						name of submit button changed to 'Submit'		*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/02/07		event detail information on individual moved	*
 *						into XML <event> tags 							*
 *		2014/10/16		event types for basic events changed by			*
 *						moving them into tblER							*
 *		2015/02/06		display chooser form in left side of window		*
 *		2015/02/10		set checkboxes if value from second individual	*
 *						and none from first individual					*
 *		2015/03/24		explicitly pass givenname, surname, gender,		*
 *						and birth year range to getPersonNamesXml		*
 *		2015/08/23		add support for treename						*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/01/07      name of second individual moved to Name         *
 *		                record in XML response                          *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 ************************************************************************/
function onLoad()
{
    pageInit();

    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];

		// set action methods for form as a whole
		if (form.name == 'indForm')
		{		// main form
		    form.onsubmit		= validateForm;
		    // feedback from dialog to get IDIR of second individual
		    form.callidir		= callidir;
		}

		// activate handling of key strokes in text input fields
		// including support for context specific help
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{		// loop through elements
		    var element	= formElts[i];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    // take action on specific elements by name
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;
		    switch(name)
		    {		// switch on name of element
				case 'choose':
				{	// pop up dialog of matching individuals
				    element.onclick	= chooseIndivid;
				    element.focus();
				    break;
				}	// pop up dialog of matching individuals

				case 'donotmerge':
				{	// mark to never merge
				    element.onclick	= doNotMerge;
				    break;
				}	// mark to never merge

				case 'view2':
				{	// view details of second individual
				    element.onclick	= viewSecond;
				    break;
				}	// view details of second individual

				default:
				{	// other elements
				    if (element.type == 'checkbox')
				    {
						element.onclick	= radioButton;
				    }	// check box
				    else
				    {	// other input element
						element.onkeydown	= keyDown;
						element.onchange	= change;
				    }	// other input element
				    break;
				}	// default
		    }		// switch on name of element
		}		// looping through elements
    }			// loop through all forms
}			// onLoad

/************************************************************************
 *  function validateForm												*
 *																		*
 *  This method is called when the user submits the form.				*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.  This is the onsubmit method.	*
 *																		*
 *  Input:																*
 *		event			an ONSUBMIT event								*
 *		this			<form name='indForm'>							*
 ************************************************************************/
function validateForm()
{
    var	form		= this;
    if (form.idir2.value.length == 0)
    {			// second individual not chosen
		 form.Submit.disabled	= true;
		 return false;
    }			// second individual not chosen
    return true;
}		// validateForm

/************************************************************************
 *  function chooseIndivid												*
 *																		*
 *  This method is called when the user requests to choose				*
 *  a second individual to merge with the current individual.			*
 *  This is the onclick method of <button id='choose'>					*
 *																		*
 *  Input:																*
 *		this		<button id='choose'>								*
 ************************************************************************/
function chooseIndivid()
{
    var	form		= this.form;
    var	idir		= form.idir1.value;
    if (idir)
    {
		var	surname		= form.surname.value;
		var	given		= form.givenpre.value;
		var	treename	= form.treename.value;
		var	gender		= form.gender.value;
		var	birthmin	= form.birthmin.value;
		var	birthmax	= form.birthmax.value;
		var	url		    = "chooseIndivid.php?idir=" + idir +
					            		    '&surname=' + surname +
					            		    '&given=' + given +
					            		    '&treename=' + treename +
					            		    '&gender=' + gender +
					            		    '&birthmin=' + birthmin +
					            		    '&birthmax=' + birthmax +
					            		    '&callidir=' + form.name;
		if (debug.toLowerCase() == 'y')
		    popupAlert("mergeIndivid.js: chooseIndivid: url='" + url + "'",
				       this);
		openFrame("chooser",
				  url,
				  "left");
    }		// idir field present
    else
		popupAlert("mergeIndivid.js: chooseIndivid: " +
						"unable to get value of idir from form",
				   this);
    return true;
}	// chooseIndivid

/************************************************************************
 *  function doNotMerge													*
 *																		*
 *  This method is called when the user requests to choose				*
 *  a second individual to merge with the current individual.			*
 *  This is the onclick method of <button id='donotmerge'>				*
 *																		*
 *  Input:																*
 *		this		<button id='donotmerge'>							*
 ************************************************************************/
function doNotMerge()
{
    var	form	= this.form;
    var	idir1	= form.idir1.value;
    var	idir2	= form.idir2.value;
    if (idir1 && idir2)
    {		// idir fields present
		var parms		= {"idirleft"  :	idir1,
							   "idirright" :	idir2};
		HTTP.post("addDontMergeXml.php",
				  parms,
				  gotDoNotMerge,
				  noDoNotMerge);
    }		// idir fields present
    else
		alert("mergeIndivid.js: doNotMerge: unable to get value of idirs from form");
    return true;
}	// doNotMerge

/************************************************************************
 *  function gotDoNotMerge												*
 *																		*
 *  This method is called when the XML file representing				*
 *  the result of a request to delete an alternate name record			*
 *  is retrieved from the database server.								*
 *																		*
 *  Input:																*
 *		xmlDoc		an XML document containing response from script		*
 *					addDontMergeXml.php								    *
 ************************************************************************/
function gotDoNotMerge(xmlDoc)
{
    if (xmlDoc === undefined)
    {
		alert("mergeIndivid.js: gotDoNotMerge: xmlDoc is undefined!");
		return;
    }
    var	form		= document.indForm;

    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName == 'added')
    {		// expected root node name
		window.refresh();
    }		// expected root node name
    else
    if (root && root.nodeName == 'msg')
    {		// error message
		alert("mergeIndivid.js: gotDoNotMerge: msg=" + root.textContent);
    }		// error message
    else
		alert("mergeIndivid.js: gotDoNotMerge: unexpected root node <" + 
				root.nodeName + ">");
}		// gotDoNotMerge

/************************************************************************
 *  noDoNotMerge														*
 *																		*
 *  This method is called if there is no response to adding a do not	*
 *  merge indviduals record.											*
 ************************************************************************/
function noDoNotMerge()
{
    alert("mergeIndivid.js: noDoNotMerge: 'addDoNotMergeXml.php' script not found on server");
}		// noDoNotMerge

/************************************************************************
 *  viewSecond															*
 *																		*
 *  This method is called when the user requests to view the details of	*
 *  the second individual to merge with the current individual.			*
 *  This is the onclick method of <button id='view2'>					*
 *																		*
 *  Input:																*
 *		this		<button id='view2'>									*
 ************************************************************************/
function viewSecond()
{
    var	form	= this.form;
    if (form)
    {
		var	idir2	= form.idir2.value;
		if (idir2)
		{		// idir field present
		    // popup new window
		    window.open("Person.php?idir=" + idir2,
						"individ2");
		}		// idir field present
    }		// form present
    else
		alert("mergeIndivid.js: viewSecond: unable to get form");
    return true;
}	// viewSecond

/************************************************************************
 *  callidir															*
 *																		*
 *  This callback method is called by the chooseIndivid.php script		*
 *  when the user has chosen an individual to merge.					*
 *																		*
 *  Input:																*
 *		idir			IDIR value of the individual to merge			*
 *		this			instance of HtmlFormElement						*
 ************************************************************************/
function callidir(idir)
{
    var	form	= this;
    HTTP.getXML("getPersonXml.php?idir=" + idir,
				gotIndiv,
				noIndiv);
}	// callidir

/************************************************************************
 *  gotIndiv															*
 *																		*
 *  This method is called when the XML file representing				*
 *  the individual is retrieved from the database.						*
 *																		*
 *  Parameters:															*
 *		xmlDoc	information about the defined individual as an XML		*
 *				document												*
 ************************************************************************/
function gotIndiv(xmlDoc)
{
    var form	= document.indForm;
    if (xmlDoc === null)
    {
		alert("mergeIndivid.js: gotIndiv: " + 
		      "System Error, no response from script getPersonXml.php");
		return;
    }
    if (xmlDoc.documentElement)
		root	= xmlDoc.documentElement;
    else
		root	= null;
    if (root)
    {
		if (root.nodeName == 'indiv')
		{		// valid response
		    var	indiv		= getObjFromXml(root);

		    var	idir		= indiv.idir;
		    var	surname		= indiv.names.name.surname;
		    var	givenname	= indiv.names.name.givenname;
		    var	birthd		= "";
		    var	birthloc	= "";
		    var	chrisd		= "";
		    var	chrisloc	= "";
		    var	deathd		= "";
		    var	deathloc	= "";
		    var	buriedd		= "";
		    var	buriedloc	= "";

		    var	events		= indiv.event;
		    if (typeof(events) == "object")
		    {			// have events and is object or array
				if (!(events instanceof Array))
				    events	= new Array(events);
				// get information from events
				for (var j = 0; j < events.length; j++)
				{		// loop through events
				    var	evnt	= events[j];
				    if (evnt.idet)
				    {		// idet attribute defined
						switch(evnt.idet)
						{	// act on specific events
						    case '3':
						    case '2000':
						    {	// birth event
							birthd		= evnt.eventd;
							birthloc	= evnt.eventloc;
							break;
						    }	// birth event

						    case '5':
						    case '3000':
						    {	// christening event
							chrisd		= evnt.eventd;
							chrisloc	= evnt.eventloc;
							break;
						    }	// chris event

						    case '6':
						    case '4000':
						    {	// death event
							deathd		= evnt.eventd;
							deathloc	= evnt.eventloc;
							break;
						    }	// death event

						    case '4':
						    case '5000':
						    {	// buried event
							buriedd		= evnt.eventd;
							buriedloc	= evnt.eventloc;
							break;
						    }	// buried event

						}	// act on specific events
				    }		// idet attribute defined
				}		// loop through events
		    }			// have events and is object or array

		    // fill in the values for the second individual
		    form.idir2.value		= idir;
		    form.Surname2.value		= surname;
		    form.GivenName2.value	= givenname;
		    form.BirthDate2.value	= birthd;
		    if (form.BirthDate1.value.length == 0 &&
				birthd.length > 0)
		    {
				form.BthDateCb1.checked	= false;
				form.BthDateCb2.checked	= true;
		    }

		    form.BirthLocation2.value	= birthloc;
		    if (form.BirthLocation1.value.length == 0 &&
				birthloc.length > 0)
		    {
				form.BthLocCb1.checked	= false;
				form.BthLocCb2.checked	= true;
		    }

		    form.ChrisDate2.value	= chrisd;
		    if (form.ChrisDate1.value.length == 0 &&
				chrisd.length > 0)
		    {
				form.CrsDateCb1.checked	= false;
				form.CrsDateCb2.checked	= true;
		    }

		    form.ChrisLocation2.value	= chrisloc;
		    if (form.ChrisLocation1.value.length == 0 &&
				chrisloc.length > 0)
		    {
				form.CrsLocCb1.checked	= false;
				form.CrsLocCb2.checked	= true;
		    }

		    form.DeathDate2.value	= deathd;
		    if (form.DeathDate1.value.length == 0 &&
				deathd.length > 0)
		    {
				form.DthDateCb1.checked	= false;
				form.DthDateCb2.checked	= true;
		    }

		    form.DeathLocation2.value	= deathloc;
		    if (form.DeathLocation1.value.length == 0 &&
				deathloc.length > 0)
		    {
				form.DthLocCb1.checked	= false;
				form.DthLocCb2.checked	= true;
		    }

		    form.BuriedDate2.value	= buriedd;
		    if (form.BuriedDate1.value.length == 0 &&
				buriedd.length > 0)
		    {
				form.BurDateCb1.checked	= false;
				form.BurDateCb2.checked	= true;
		    }

		    form.BuriedLocation2.value	= buriedloc;
		    if (form.BuriedLocation1.value.length == 0 &&
				buriedloc.length > 0)
		    {
				form.BurLocCb1.checked	= false;
				form.BurLocCb2.checked	= true;
		    }

		    // now that we have both individuals, enable submit
		    form.Submit.disabled	= false;
		    form.donotmerge.disabled	= false;
		    form.view2.disabled		= false;
		    form.Submit.focus();	
		}		// valid response
		else
		    alert("mergeIndivid.js: gotIndiv: root=" + tagToString(root));
    }
    else
    {
		alert("mergeIndivid.js: gotIndiv: xmlDoc=" + xmlDoc);
    }
}		// gotIndiv

/************************************************************************
 *  noIndiv																*
 *																		*
 *  This method is called if there is no individual						*
 *  file.																*
 ************************************************************************/
function noIndiv()
{
    alert("mergeIndivid.js: noIndiv: document not found on server");
}		// noIndiv

/************************************************************************
 *  radioButton																*
 *																		*
 *  This method is called when the user clicks on a checkbox				*
 *  to choose one of a pair of values.										*
 *																		*
 *  Input:																*
 *		this		instance of HtmlInputElement								*
 ************************************************************************/
function radioButton()
{
    if (this.form.idir2.value.length == 0)
		return false;	// do not set if no second individual

    // identify the set of radio buttons to which this button belongs
    // and which column of the set it is in
    var	set	= this.name.substring(0, this.name.length - 1);
    var col	= this.name.substring(this.name.length - 1);

    // check this box and uncheck the other box in the same set
    this.checked	= true;
    if (col == '1')
    {
		this.form.elements[set + '2'].checked	= false;	
    }
    else
    {
		this.form.elements[set + '1'].checked	= false;	
    }

    return true;
}		// radioButton
