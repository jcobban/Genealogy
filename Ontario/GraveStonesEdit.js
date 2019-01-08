/************************************************************************
 *  GraveStonesEdit.js							*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  GraveStonesEdit.php							*
 *									*
 *  History:								*
 *	2012/05/16	created						*
 *	2013/08/01	defer facebook initialization until after load	*
 *									*
 *  Copyright &copy; 2013 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize the dynamic functionality once the page is loaded	*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
	var form	= document.forms[fi];

	var	setFocusOnSurname	= true;
	for (var i = 0; i < form.elements.length; ++i)
	{	// loop through all elements of form
	    element		= form.elements[i];
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
		element.onmouseover		= eltMouseOver;
		element.onmouseout		= eltMouseOut;
	    }	// set mouseover on input element itself

	    var	name		= element.name;
	    if (element.name == '')
		name		= element.id;
	    var	rownum		= '';

	    var results		= /^([a-zA-Z]+)(\d*)/.exec(name);
	    if (results)
	    {
		name		= results[1];
		rownum		= results[2];
	    }

	    switch(name.toLowerCase())
	    {			// act on specific fields 
		case 'add':
		{
		    element.helpDiv	= 'Add';
		    element.onclick	= addRow;
		    break;
		}

		case 'delete':
		{
		    element.helpDiv	= 'Delete';
		    element.onclick	= deleteRow;
		    break;
		}

		case 'county':
		{
		    element.onchange	= changeCounty;
		    break;
		}

		case 'township':
		{
		    element.onchange	= changeTownship;
		    break;
		}

		case 'cemetery':
		{
		    element.onchange	= changeCemetery;
		    break;
		}

		case 'zone':
		{
		    if ('cemetery' in args && !('zone' in args))
		    {
			element.focus();
		 	setFocusOnSurname	= false;
		    }
		    element.onchange	= changeZone;
		    break;
		}

		case 'row':
		{
		    if ('zone' in args && !('row' in args))
		    {
			element.focus();
		 	setFocusOnSurname	= false;
		    }
		    element.onchange	= changeRow;
		    break;
		}

		case 'plot':
		{
		    if ('row' in args && !('plot' in args))
		    {
			element.focus();
		 	setFocusOnSurname	= false;
		    }
		    element.onchange	= changePlot;
		    break;
		}

		case 'side':
		{
		    element.onchange	= changeReplDown;
		    element.onkeydown	= tableKeyDown;
		    break;
		}

		case 'surname':
		{
		    if (setFocusOnSurname)
		    {
			element.focus();
		 	setFocusOnSurname	= false;
		    }
		    element.onchange	= changeReplDown;
		    element.onkeydown	= tableKeyDown;
		    break;
		}

		case 'givenname':
		case 'birthdate':
		case 'deathdate':
		case 'text':
		{
		    element.onkeydown	= tableKeyDown;
		    break;
		}

	    }			// act on specific fields 
	}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  changeCounty							*
 *									*
 *  The user has selected a different county				*
 *									*
 *  Input:								*
 *	this		<select name='County'>				*
 ************************************************************************/
function changeCounty()
{
    var	county		= this.value;
    location		= 'GraveStonesEdit.php?County=' + county;
}	// function changeCounty

/************************************************************************
 *  changeTownship							*
 *									*
 *  The user has selected a different township				*
 *									*
 *  Input:								*
 *	this		<select name='County'>				*
 ************************************************************************/
function changeTownship()
{
    var	form		= this.form;
    var	township	= this.value;
    var	county		= form.County.value;
    location		= 'GraveStonesEdit.php?County=' + county +
				'&Township=' + township;
}	// function changeTownship

/************************************************************************
 *  changeCemetery							*
 *									*
 *  The user has selected a different cemetery				*
 *									*
 *  Input:								*
 *	this		<select name='Cemetery'>			*
 ************************************************************************/
function changeCemetery()
{
    var	form		= this.form;
    var	cemetery	= this.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    location		= 'GraveStonesEdit.php?County=' + county +
				'&Township=' + township +
				'&Cemetery=' + cemetery;
}	// function changeCemetery

/************************************************************************
 *  changeZone								*
 *									*
 *  The user has selected a different section within a cemetery		*
 *									*
 *  Input:								*
 *	this		<select name='Zone'>				*
 ************************************************************************/
function changeZone()
{
    var	form		= this.form;
    var	zone		= this.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    var	cemetery	= form.Cemetery.value;
    location		= 'GraveStonesEdit.php?County=' + county +
				'&Township=' + township +
				'&Cemetery=' + cemetery +
				'&Zone=' + zone;
}	// function changeZone

/************************************************************************
 *  changeRow								*
 *									*
 *  The user has selected a different row				*
 *									*
 *  Input:								*
 *	this		<input type='text' name='Row'>			*
 ************************************************************************/
function changeRow()
{
    var	form		= this.form;
    var	row		= this.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    var	cemetery	= form.Cemetery.value;
    var	zone		= form.Zone.value;
    location		= 'GraveStonesEdit.php?County=' + county +
				'&Township=' + township +
				'&Cemetery=' + cemetery +
				'&Zone=' + zone +
				'&Row=' + row;
}	// function changeRow

/************************************************************************
 *  changePlot								*
 *									*
 *  The user has selected a different plot				*
 *									*
 *  Input:								*
 *	this		<input type='text' name='Plot'>			*
 ************************************************************************/
function changePlot()
{
    var	form		= this.form;
    var	plot		= this.value;
    var	county		= form.County.value;
    var	township	= form.Township.value;
    var	cemetery	= form.Cemetery.value;
    var	zone		= form.Zone.value;
    var	row		= form.Row.value;
    location		= 'GraveStonesEdit.php?County=' + county +
				'&Township=' + township +
				'&Cemetery=' + cemetery +
				'&Zone=' + zone +
				'&Row=' + row +
				'&Plot=' + plot;
}	// function changePlot

/************************************************************************
 *  addRow								*
 *									*
 *  The user has requested to add a Row after the current row		*
 *									*
 *  Input:								*
 *	this		<button id='Add999'>				*
 ************************************************************************/
function addRow()
{
    var	table		= document.getElementById('dataTbl');
    var	tbody		= table.tBodies[0];
    var num		= tbody.rows.length - 1;
    var lastrow		= tbody.rows[num];
    var	rowid		= lastrow.id;
    var results		= /\d+$/.exec(rowid);
    var rownum		= results[0];
    var newrownum	= (rownum - 0) + 1;
    var	pattern		= new RegExp(rownum + '"', 'g');
    var	oldInner	= lastrow.innerHTML;
    var	replace		= newrownum + '"';
    var	newInner	= oldInner.replace(pattern, newrownum + '"');
    newInner		= newInner.replace(/value="\d+"/, 'value="0"');
    var row		= tbody.insertRow();
    row.id		= 'Row' + newrownum;
    row.innerHTML	= newInner;
}		// addRow

/************************************************************************
 *  deleteRow								*
 *									*
 *  The user has requested to delete the current row			*
 *									*
 *  Input:								*
 *	this		instance of <button id='Delete9'>		*
 ************************************************************************/
function deleteRow()
{
    var	form		= this.form;
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    var	tbody		= row.parentNode;
    var results		= /\d+$/.exec(this.id);
    var rownum		= results[0];
    var	element		= form.elements['GivenName' + rownum];
    element.value	= '';
    var	element		= form.elements['Surname' + rownum];
    element.value	= '';
    var	element		= form.elements['Text' + rownum];
    element.value	= '';
    var	element		= form.elements['BirthDate' + rownum];
    element.value	= 0;
    var	element		= form.elements['DeathDate' + rownum];
    element.value	= 0;
    row.style.display	= 'none';
}		// deleteRow

/************************************************************************
 *  changeReplDown							*
 *									*
 *  Take action when the user changes a field whose value is		*
 *  replicated into subsequent fields in the same column whose		*
 *  value has not yet been explicitly set.				*
 *									*
 *  Input:								*
 *	this	instance of <input type='text'>				*
 ************************************************************************/
function changeReplDown()
{
    var	form		= this.form;
    var	name		= this.name;
    if (this.id)
	name		= this.id;

    if (this.abbrTbl)
	expAbbr(this, this.abbrTbl);

    // shortcut for next incremental value
    if (this.value == '+')
    {				// get next incremental value
	var	result	= /\d+$/.exec(name);
	if (result)
	{			// got row number
	    var	rowNum		= result[0];
	    var rowNumLen	= rowNum.length;
	    var	columnName	= name.substring(0, name.length - rowNumLen);
	    var	prevElement	= null;
	    while(this.value == '+')
	    {			// find last non-empty field
		rowNum		= rowNum - 1;
		if (rowNum >= 10)
		{		// 2 digit row number
		    prevElement	= form.elements[columnName + rowNum];
		    if (/\d+/.exec(prevElement.value))
		    {
			this.value	= prevElement.value - 0 + 1;
			break;
		    }
		}		// 2 digit row number
		else
		if (rowNum > 0)
		{		// 1 digit row number
		    prevElement	= form.elements[columnName + '0' + rowNum];
		    if (/\d+/.exec(prevElement.value))
		    {
			this.value	= prevElement.value - 0 + 1;
			break;
		    }
		}		// 1 digit row number
		else
		{		// not found on this page
		    // take value from last element on previous page
		    this.value	= this.defaultValue - 0 + 1;
		    break;
		}		// not found on this page
	    }			// find last non-empty field
	}			// got row number
	else			// take value from last element on previous page
	    this.value	= this.defaultValue + 1;
    }				// get next incremental value

    // replicate the value into subsequent rows
    if (this.value.length > 0)
	replDown(this);

    // validate the contents of the field
    if (this.checkfunc)
	this.checkfunc();
}		// changeReplDown

/************************************************************************
 *  replDown								*
 *									*
 *  Replicate the value of the current element into 			*
 *  subsequent elements in the current column whose			*
 *  value has not yet been explicitly set.				*
 *									*
 *  Input:								*
 *	curr	instance of <input type='text'>				*
 ************************************************************************/
function replDown(curr)
{
    // change the presentation of the current field
    if (curr.className.substr(0,3) == "dft")
    {	// value has been modified
	curr.className = "black white " + curr.className.substr(3);
    }	// value has been modified
    else
    if (curr.className.substr(0,5) == 'same ')
    {	// value has been modified
	curr.className = "black white " + curr.className.substr(5);
    }	// value has been modified

    // update the presented values of curr field in subsequent rows
    var	form		= curr.form;
    var	name		= curr.name;
    var	newValue	= curr.value;
    var	results		= /\d+$/.exec(name);
    var	rowNum		= results[0];
    var	column		= name.substring(0,name.length - rowNum.length);
    var blankrow	= newValue.toLowerCase() == '[delete]';

    for (rowNum++; ; rowNum++)
    {
	var field	= form.elements[column + rowNum];

	if (field === undefined)
	    break;
	field.value	= newValue
    }		// loop to end of page

}		// function replDown

