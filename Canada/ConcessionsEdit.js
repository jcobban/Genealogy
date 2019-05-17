/************************************************************************
 *  ConcessionsEdit.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  ConcessionsEdit.php													*
 *																		*
 *  History:															*
 *		2012/06/18		created											*
 *		2018/10/24      change implementation of delete                 *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *																		*
 *  Input:																*
 *		this			window											*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	= '';
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
		    trace += "<" + element.nodeName + " ";
		    if (element.name.length > 0)
				trace	+= "name='" + element.name + "' ";
		    if (element.id.length > 0)
				trace	+= "id='" + element.id + "' ";
		    trace	+= ">";
		    element.onkeydown	= keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	// set mouseover on containing cell
		    else
		    {	// set mouseover on input element itself
				element.onmouseover		        = eltMouseOver;
				element.onmouseout		        = eltMouseOut;
		    }	// set mouseover on input element itself

		    var name		= element.id;
		    if (name == '')
				name		= element.name;
		    var	line		= '';
		    var regexResult	= /^([a-zA-Z_$]+)(\d*)/.exec(name);
		    if (regexResult)
		    {
				name		= regexResult[1];
				line		= regexResult[2];
		    }

		    switch(name.toLowerCase())
		    {			// act on column name
				case 'add':
				{
				    element.onclick	= addDialog;
				    break;
				}

				case 'conid':
				{
				    element.helpDiv	= 'ConID';
				    element.change	= changeConID;
				    element.checkfunc	= checkText;
				    break;
				}

				case 'plot':
				{
				    element.helpDiv	= 'Plot';
				    element.onclick	= plotLot;
				    break;
				}

				case 'add':
				{
				    element.helpDiv	= 'Add';
				    element.onclick	= addDialog;
				    break;
				}

				case 'delete':
				{
				    element.helpDiv	= 'Delete';
				    element.onclick	= deleteConcession;
				    break;
				}

		    }			// act on column name
		}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  function plotLot													*
 *																		*
 *  When a Plot button is clicked this function displays the			*
 *  Google map for lot 1 of the concession.								*
 *																		*
 *  Input:																*
 *		this			<button id='Plot...'>							*
 ************************************************************************/
function plotLot()
{
    popupAlert("plot", this);
    return false;
}		// plotLot

/************************************************************************
 *  function deleteConcession											*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		this			<button id='Delete...'>							*
 ************************************************************************/
function deleteConcession()
{
    var line    = this.id.substring(6);
    var element = document.getElementById('ConID' + line);
    element.value   = 'deleted';
    element.defaultValue   = 'deleted';
    element.type    = 'hidden';
    element = document.getElementById('FirstLot' + line);
    element.type    = 'hidden';
    element = document.getElementById('LastLot' + line);
    element.type    = 'hidden';
    element = document.getElementById('Latitude' + line);
    element.type    = 'hidden';
    element = document.getElementById('Longitude' + line);
    element.type    = 'hidden';
    element = document.getElementById('LatByLot' + line);
    element.type    = 'hidden';
    element = document.getElementById('LongByLot' + line);
    element.type    = 'hidden';
    element = document.getElementById('Plot' + line);
    element.disabled= true;
    this.disabled   = true;
    return false;
}		// deleteConcession

/************************************************************************
 *  function addDialog													*
 *																		*
 *  When the Add county button is clicked this function pops up a		*
 *  dialog to add rows into the table.									*
 *																		*
 *  Input:																*
 *		this			<button id='Add...'>							*
 ************************************************************************/
function addDialog()
{
    var	form		= this.form;
    var	line		= this.id.substring(3);
    var	table		= document.getElementById("dataTable");
    var	tbody		= table.tBodies[0];
    var	domain		= form.Domain.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    var	cc		    = domain.substring(0,2);
    var	province	= domain.substring(2,2);
    var	parms	    = {"domain"	    : domain,
				       "prov"	    : province,
				       "county"	    : county,
				       "township"	: township,
				       "line"	    : line,
				       "formname"	: form.name,
				       "template"	: ''};
    dialogDiv	= document.getElementById('msgDiv');
    displayDialog(dialogDiv,
				  "AddDialog$template",
				  parms,
				  this,		    	// position relative to
				  performAdd,		// 1st button performs add
				  false);		    // default show on open

    var element		    = document.getElementById('Count');
    element.onclick	    = focusCount;
    element.onchange	= change;
    element.checkfunc	= checkNumber;

    return false;
}		// function addDialog

/************************************************************************
 *  function focusCount													*
 *																		*
 *  Take special action when the user clicks on the Count field in		*
 *  the add dialog.														*
 *																		*
 *  Input:																*
 *		$this			<input id='Count'>								*
 ************************************************************************/
function focusCount()
{
    this.focus();
    this.select();
}		// function focusCount

/************************************************************************
 *  function performAdd													*
 *																		*
 *  Take special action when the user changes the identifier field.		*
 *																		*
 *  Input:																*
 *		$this			<input id='ConID...'>							*
 ************************************************************************/
function performAdd()
{
    var form		= this.form;
    var position	= form.Position.value;
    var	count		= form.Count.value;
    var	domain		= form.Domain.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    var	line		= form.line.value;

    // position to insert at
    var row		    = document.getElementById('Row' + line);
    var	tbody		= row.parentNode;
    var	newline		= tbody.rows.length + 1;
    var	conid		= '??' + newline;
    if (position == 'after')
		row		    = row.nextSibling;

    var parms	= {'conid'	    : conid,
				   'codeclass'	: 'actleftncerror',
				   'numclass'	: 'actrightnc',
				   'line'	    : newline,
				   'order'	    : newline,
				   'firstlot'	: 1,
				   'lastlot'	: 20,
				   'latitude'	: 0.0,
				   'longitude'	: 0.0,
				   'latbylot'	: 0.0,
				   'longbylot'	: 0.0};
    for (; count; count--)
    {
		parms.conid	= '??' + parms.line;
		var	newRow	= createFromTemplate("Row$line",
								         parms,
								         null);
		tbody.insertBefore(newRow, row);
		var element	= document.getElementById('ConID' + parms.line)
		if (element)
		{
		    element.helpDiv		= 'ConID';
		    element.change		= changeConID;
		    element.focus();
		}
		else
		    alert("could not locate element id='ConID" + parms.line + "'");
		element		        = document.getElementById('Plot' + parms.line)
		element.helpDiv		= 'Plot';
		element.onclick		= plotLot;
		element		        = document.getElementById('Add' + parms.line)
		element.helpDiv		= 'Add';
		element.onclick		= addDialog;
		element		        = document.getElementById('Delete' + parms.line)
		element.helpDiv		= 'Delete';
		element.onclick		= deleteConcession;
		parms.line++;
    }

    // set sort order of rows to match display order
    for(var il = 0; il < tbody.rows.length; il++)
    {
		var row		= tbody.rows[il];
		var rowid	= row.id;
		var line	= rowid.substring(3);
		var order	= document.getElementById('Order' + line);
		order.value	= il + 1;
    }
}		// function performAdd

/************************************************************************
 *  function changeConID												*
 *																		*
 *  Take special action when the user changes the identifier field.		*
 *																		*
 *  Input:																*
 *		$this				<input id='ConID...'>						*
 ************************************************************************/
function changeConID()
{
    var form		= this.form;
    var	line		= this.name.substring(4);

    if (this.checkfunc)
		this.checkfunc();
}		// function changeConID

