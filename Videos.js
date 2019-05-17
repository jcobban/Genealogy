/************************************************************************
 *  Videos.js															*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  Videos.php															*
 *																		*
 *  History:															*
 *		2018/02/01		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
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
		element.onmouseover		= eltMouseOver;
		element.onmouseout		= eltMouseOut;
	    }	// set mouseover on input element itself

	    var	name	= element.name;
	    if (name.length == 0)
		name	= element.id;
	    var	column	= name;
	    var	row	= '';
	    var matches	= /^([a-zA-Z]+)(\d*)$/.exec(name);
	    if (matches)
	    {
		column	= matches[1].toLowerCase();
		row	= matches[0];
	    }

	    switch (column)
	    {		// act on a field from a table row
		case 'filename':
		{	// file name
		    element.helpDiv	= 'FileName';
		    element.change	= change;
		    break;
		}	// file name

		case 'lang':
		{	// the language of the description
		    element.helpDiv	= 'Lang';
		    element.change	= change;
		    break;
		}	// the language of the description

		case 'description':
		{	// description of the video
		    element.helpDiv	= 'Description';
		    element.change	= change;
		    break;
		}	// description of the video

		case 'display':
		{	// whether or not to display the video
		    element.helpDiv	= 'Display';
		    element.change	= change;
		    break;
		}	// whether or not to display the video

		case 'deletebutton':
		{	// delete this row
		    element.helpDiv	= 'Delete';
		    element.onclick	= deleteVideo;
		    break;
		}	// delete this row

		case 'add':
		{
		    element.helpDiv	= 'Add';
		    element.onclick	= addVideo;
		    break;
		}
	    }			// act on a field
	}			// loop through all elements in the form
    }				// loop through all forms
}		// function onLoad

/************************************************************************
 *  deleteVideo																*
 *																		*
 *  When a Delete button is clicked this function visually removes the		*
 *  row from the table and sets a flag so when the Update button is		*
 *  clicked the record will be deleted.										*
 *																		*
 *  Input:																*
 *		$this				<button type=button id='DeleteButton....'		*
 ************************************************************************/
function deleteVideo()
{
    var	rownum		= this.id.substring(12);
    var	form		= this.form;
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    var	deleteField	= document.getElementById('Delete' + rownum);
    if (deleteField)
	deleteField.value	= 'Y';		// passed to update script
    row.style.display	= 'none';	// hide the row

    return false;
}		// deleteVideo

/************************************************************************
 *  function addVideo													*
 *																		*
 *  When the Add row button is clicked this function adds a row			*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this		<button type=button id='Add'>						*
 ************************************************************************/
function addVideo()
{
    var	table		= document.getElementById("dataTable");
    var	tbody		= table.tBodies[0];
    var numRows		= tbody.rows.length;
    var	rowClass	= 'odd';
    if (numRows & 1)
	rowClass	= 'even';	// next is even
    var	parms		= {"row"	: numRows + 1,
			   "even"	: rowClass};
    var	template	= document.getElementById("video$row");
    var	newRow		= createFromTemplate(template,
					     parms,
					     null);
    tbody.appendChild(newRow);

    return false;
}		// addVideo
