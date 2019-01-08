/************************************************************************
 *  CountiesEdit.js														*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountiesEdit.php													*
 *																		*
 *  History:															*
 *		2012/05/08		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/11/03		support domains other than in Canada			*
 *						set focus on code column of added county		*
 *		2017/02/07		use class Country								*
 *						add button to edit associated Location			*
 *		2018/02/03		if county name ends in ' District' do not		*
 *						insert ' Co'									*
 *		2018/10/08      blank out row for county delete                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	= "";
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];
		trace	+= "<form ";
		if (form.name.length > 0)
		    trace	+= "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	+= "id='" + form.id + "' ";
		trace	+= ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    trace		+= "<" + element.nodeName + " ";
		    if (element.name.length > 0)
				trace		+= "name='" + element.name + "' ";
		    if (element.id.length > 0)
				trace		+= "id='" + element.id + "' ";
		    trace		+= ">";
		    element.onkeydown	= keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == "TD")
		    {	// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	// set mouseover on containing cell
		    else
		    {	// set mouseover on input element itself
				element.onmouseover		= eltMouseOver;
				element.onmouseout		= eltMouseOut;
		    }	// set mouseover on input element itself

		    if (element.id == "Add")
		    {
				element.onclick	= addCounty;
		    }
		    else
		    if (element.name.substring(0, 4) == "Code")
		    {
				element.helpDiv	= "Code";
		    }
		    else
		    if (element.name.substring(0, 4) == "Name")
		    {
				element.helpDiv	= "Name";
				element.change	= change;
		    }
		    else
		    if (element.name.substring(0, 9) == "StartYear")
		    {
				element.helpDiv	= "StartYear";
		    }
		    else
		    if (element.name.substring(0, 7) == "EndYear")
		    {
				element.helpDiv	= "EndYear";
		    }
		    else
		    if (element.id.substring(0, 6) == "Delete")
		    {
				element.helpDiv	= "Delete";
				element.onclick	= deleteCounty;
		    }
		    else
		    if (element.id.substring(0, 13) == "EditTownships")
		    {
				element.helpDiv	= "EditTownships";
				element.onclick	= showTownships;
		    }
		    else
		    if (element.id.substring(0, 12) == "EditLocation")
		    {
				element.helpDiv	= "EditLocation";
				element.onclick	= editLocation;
		    }
		}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  changeCode															*
 *																		*
 *  Take action when the user changes the county code.					*
 *																		*
 *  Input:																*
 *		$this			instance of <input name="Code...">				*
 ************************************************************************/
function changeCode()
{
    changeElt(this);
    var	oldCode	= this.name.substring(4);
    var newCode	= this.value;
    var	form	= this.form;
    this.name					= "Code" + newCode;
    if (form.elements["Code" + newCode])
    {		// successfully renamed the current element
		// alert("changeCode: oldCode="" + oldCode + "", newCode="" + newCode + """ +
		//	tagToString(this.parentNode.parentNode));
		form.elements["Name" + oldCode].name		= "Name" + newCode;
		form.elements["StartYear" + oldCode].name	= "StartYear" + newCode;
		form.elements["EndYear" + oldCode].name		= "EndYear" + newCode;
		var deleteBtn	= document.getElementById("Delete" + oldCode);
		deleteBtn.id					= "Delete" + newCode;
		deleteBtn.onclick				= deleteCounty;
		deleteBtn.disabled				= false;
		var townshipBtn	= document.getElementById("EditTownships" + oldCode);
		townshipBtn.id				= "EditTownships" + newCode;
		townshipBtn.onclick				= showTownships;
		townshipBtn.disabled				= false;
		var addBtn	= document.getElementById("Add");
		addBtn.disabled					= false;
    }		// successfully renamed the current element
    else
    {		// unable to rename, probably some back level of IE!
		alert("Unable to rename element Code" + oldCode + " to Code" + newCode);
    }		// unable to rename, probably some back level of IE!
}		// changeCode

/************************************************************************
 *  deleteCounty														*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		$this			<button type=button id="Delete...."				*
 ************************************************************************/
function deleteCounty()
{
    var code            = this.id.substring(6);
    var nameElt         = document.getElementById('Name' + code);
    if (nameElt === null)
        alert("could not get <input id='Name" + code + "'");
    nameElt.value       = '';
    nameElt.defaultValue= '';
    nameElt.type        = 'hidden';
    var startElt        = document.getElementById('StartYear' + code);
    startElt.value      = '';
    startElt.defaultValue= '';
    startElt.type       = 'hidden';
    var endElt          = document.getElementById('EndYear' + code);
    endElt.value        = '';
    endElt.defaultValue = '';
    endElt.type         = 'hidden';
    this.disabled       = true;
    var editElt         = document.getElementById('EditTownships' + code);
    editElt.disabled    = true;
    var locnElt         = document.getElementById('EditLocation' + code);
    locnElt.disabled    = true;
    return false;
}		// deleteCounty

/************************************************************************
 *  showTownships														*
 *																		*
 *  When a Townships button is clicked this function displays the		*
 *  edit dialog for the list of townships in a county.					*
 *																		*
 *  Input:																*
 *		$this			<button type=button id="EditTownships...."		*
 ************************************************************************/
function showTownships()
{
    var	form	= this.form;
    var	domain	= form.Domain.value;
    var	county	= this.id.substring(13);
    var	lang		= 'en';
   
    if (form.lang)
		lang		= form.lang.value;
    else 
    if ('lang' in args)
		lang		= args['lang'];

    window.open("TownshipsEdit.php?Domain=" + domain + 
								"&County=" + county +
								"&lang=" + lang,
				"_blank");
    return false;
}		// showTownships

/************************************************************************
 *  editLocation														*
 *																		*
 *  When a Location button is clicked this function displays the		*
 *  edit dialog for the Location record associated with the county.		*
 *  This always succeeds even if the record does not exist yet.			*
 *																		*
 *  Input:																*
 *		$this			<button type=button id="EditLocation...."		*
 ************************************************************************/
function editLocation()
{
    var	form		= this.form;
    var	domain		= form.Domain.value;
    var	cc		= domain.substring(0,2);
    var	state		= domain.substring(2);
    var	countyCode	= this.id.substring(12);
    var	countyName	= form.elements["Name" + countyCode].value;
    var	lang		= 'en';
   
    if (form.lang)
		lang		= form.lang.value;
    else 
    if ('lang' in args)
		lang		= args['lang'];

    var	url	= "/FamilyTree/Location.php?name=";
    if (countyName.substr(countyName.length - 9) == ' District' ||
		countyName.substr(countyName.length - 3) == ' Co' ||
		countyName.substr(countyName.length - 7) == ' County')
		url	+= encodeURIComponent(countyName + ", " + state + ", " + cc);
    else
		url	+= encodeURIComponent(countyName + " Co, " + state + ", " + cc);
    url		+= "&lang=" + lang;
    window.open(url,
				"_blank");
    return false;
}		// editLocation

/************************************************************************
 *  addCounty															*
 *																		*
 *  When the Add county button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this			<button type=button id="Add">					*
 ************************************************************************/
function addCounty()
{
    this.disabled	= true;	// only permit one row to be added
    var	form		= this.form;
    var	parms	= {"domain"	: form.Domain.value,
				   "code"	: "Xxx"};
    var	template	= document.getElementById("Row$code");
    var	newRow		= createFromTemplate(template,
									     parms,
									     null);
    var	table		= document.getElementById("dataTbl");
    var	tbody		= table.tBodies[0];
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added county
    var	codeElt		= form.CodeXxx;
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= changeCode;

    return false;
}		// addCounty
