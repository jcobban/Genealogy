/************************************************************************
 *  Sources.js								*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page Sources.php.							*
 *									*
 *  History:								*
 *	2010/10/17	set size of edit dialog to avoid scrolling	*
 *	2011/01/31	implement creation of new source		*
 *	2012/01/13	change class names				*
 *	2013/02/21	increase size of dialog for editing a source	*
 *	2013/03/28	support mouseover help				*
 *			separate HTML and Javascript			*
 *	2013/05/29	use actMouseOverHelp common function		*
 *	2013/06/19	add code to delete visible row when source is	*
 *			deleted						*
 *	2013/08/01	defer facebook initialization until after load	*
 *	2014/12/12	enclose comment blocks				*
 *	2015/05/28	display source in split window			*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

window.onload	= onloadSources;

/************************************************************************
 *  onLoadSources							*
 *									*
 *  Initialize dynamic functionality of the page.			*
 ************************************************************************/
function onloadSources()
{
    pageInit();

    var	form			= document.srcForm;

    // set action methods for form
    form.onsubmit		= validateForm;
    form.onreset 		= resetForm;
    form.sourceCreated		= sourceCreated;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
	var element	= formElts[i];

	// pop up help balloon if the mouse hovers over a field
	// for more than 2 seconds
	actMouseOverHelp(element);

	element.onkeydown	= keyDown;
	element.onchange	= change;	// default handler

	// take action specific to element
	var	name;
	if (element.name && element.name.length > 0)
	    name	= element.name;
	else
	    name	= element.id;

	switch(name)
	{		// act on field name
	    case 'CreateNew':
	    {
		element.onclick	= createSource;
		break;
	    }

	    default:
	    {
		if (name.substring(0,4) == 'Edit')
		{
		    element.onclick	= editSource;
		    var idsr		= name.substring(4);
		    var row		= document.getElementById('Row' + idsr);
		    if (row)
			row.feedback	= sourceUpdated;
		}
		else
		if (name.substring(0,4) == 'Show')
		    element.onclick	= showSource;
		else
		if (name.substring(0,6) == 'Delete')
		    element.onclick	= deleteSource;
		break;
	    }
	}		// act on field name
    }		// loop through all elements in the form

}		// function onLoadSources

/************************************************************************
 *  validateForm							*
 *									*
 *  Ensure that the data entered by the user has been minimally		*
 *  validated before submitting the form.				*
 *									*
 *  Input:								*
 *	this		<form ...>					*
 ************************************************************************/
function validateForm()
{
    return true;
}		// function validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 *									*
 *  Input:								*
 *	this		<form ...>					*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  sourceCreated							*
 *									*
 *  This method is called when a child window notifies this script	*
 *  that a new source has been created.					*
 *									*
 *  Input:								*
 *	this		<form ...>					*
 ************************************************************************/
function sourceCreated()
{
    var	createButton	= document.getElementById('CreateNew');
    if (createButton)
    {
	var	parentNode	= createButton.parentNode;
	var	textNode	= document.createTextNode(
			"Reloading page to display new source in order.");
	parentNode.insertBefore(textNode, createButton.nextSibling);
    }
    location.reload(true);
    return false;
}	// sourceCreated

/************************************************************************
 *  sourceUpdated							*
 *									*
 *  This method is called when a child window notifies this script	*
 *  that an existing source has been updated.				*
 *									*
 *  Input:								*
 *	this		<tr id='Row...'>				*
 *	parms		associative array of field values		*
 ************************************************************************/
function sourceUpdated(parms)
{
    var text	= "parms={";
    for(fldname in parms)
	text	+= fldname + "='" + parms[fldname] + "',";
    var	idsr		= this.id.substring(3);
    var	cell;
    for(fldname in parms)
    {			// loop through all parameters
	switch(fldname.toLowerCase())
	{		// act on specific parameters
	    case 'srcname':
	    {		// public name of source
		cell	= document.getElementById('Name' + idsr);
		if (cell)
		    cell.innerHTML	= parms[fldname];
		break;
	    }		// public name of source

	    case 'idst':
	    {		// type of source
		cell	= document.getElementById('Type' + idsr);
		var idst	= parms[fldname];
		var type	= document.getElementById('IDST' + idst);
		if (cell)
		{
		    if (type)
		    {
			cell.innerHTML	= type.innerHTML.trim();
		    }
		    else
		    {
			cell.innerHTML	= parms[fldname];
		    }	// act on specific types
		}
		else
		    alert("Sources.js:sourceUpdated: no <element id='Type" +
			  idsr + "'");
		break;
	    }		// type of source
	}		// act on specific parameters
    }			// loop through all parameters
    return false;
}		// function sourceUpdated

/************************************************************************
 *  createSource							*
 *									*
 *  This method is called when the user requests to create		*
 *  a new Source.							*
 *									*
 *  Input:								*
 *	this		<button id='CreateNew'>				*
 ************************************************************************/
function createSource()
{
    openFrame("source",
	      "/FamilyTree/editSource.php?idsr=0&form=srcForm",
	      "right");
    return false;
}	// createSource

/************************************************************************
 *  showSource								*
 *									*
 *  This method is called when the user requests to show		*
 *  an existing Source.  It pops up a child window.			*
 *									*
 *  Input:								*
 *	this		<button id='Show....'>				*
 ************************************************************************/
function showSource()
{
    var	idsr	= this.id.substring(4);
    openFrame("source",
	      "/FamilyTree/Source.php?idsr=" + idsr,
	      "right");
    return false;
}		// showSource

/************************************************************************
 *  editSource								*
 *									*
 *  This method is called when the user requests to edit		*
 *  an existing Source.  It pops up a child window.			*
 *									*
 *  Input:								*
 *	this		<button id='Edit....'>				*
 ************************************************************************/
function editSource()
{
    var	idsr	= this.id.substring(4);
    openFrame("source",
	      "/FamilyTree/editSource.php?idsr=" + idsr +
					"&elementid=Row" + idsr,
	      "right");
    return false;
}		// editSource

/************************************************************************
 *  deleteSource							*
 *									*
 *  This method is called when the user requests to delete 		*
 *  an unreferenced existing Source.					*
 *									*
 *  Input:								*
 *	this		<button id='Delete...'>				*
 ************************************************************************/
function deleteSource()
{
    var	idsr	= this.id.substring(6);
    var parms		= { "idsr" : idsr};

    // invoke script to delete the record
    HTTP.post("/FamilyTree/deleteSourceXml.php",
	      parms,
	      gotDelete,
	      noDelete);
    return false;
}		// deleteSource

/************************************************************************
 *  gotDelete								*
 *									*
 *  This method is called when the response to the request to delete	*
 *  an event is received.						*
 *									*
 *  Parameters:								*
 *	xmlDoc		reply as an XML document			*
 ************************************************************************/
function gotDelete(xmlDoc)
{
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'deleted')
    {
	var msglist	= root.getElementsByTagName('msg');
	if (msglist.length == 0)
	{
	    var idsr	= root.getAttribute('idsr');
	    var row	= document.getElementById('Row' + idsr);
	    if (row)
	    {
		var	sect	= row.parentNode;
		sect.removeChild(row);
	    }		// have row to delete
	}
	else
	{
	    alert(tagToString(msglist.item(0)));
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
}	// gotDelete

/************************************************************************
 *  noDelete								*
 *									*
 *  This method is called if there is no response to the AJAX		*
 *  delete event request.						*
 ************************************************************************/
function noDelete()
{
    alert("Sources.js: noDelete: " +
	  "script /FamilyTree/deleteSourceXml.php not found");
}	// noDelete
