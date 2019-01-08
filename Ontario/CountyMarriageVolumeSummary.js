/************************************************************************
 *  CountyMarriageVolumeSummary.js					*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  CountyMarriageVolumeSummary.php					*
 *									*
 *  History:								*
 *	2017/07/15	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
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
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
	var form	= document.forms[fi];

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

	    var namePattern	= /^([a-zA-Z_]+)(\d*)$/;
	    var	id		= element.id;
	    if (id.length == 0)
		id		= element.name;
	    var rresult		= namePattern.exec(id);
	    var	column		= id;
	    var	rownum		= '';
	    if (rresult !== null)
	    {
		column		= rresult[1];
		rownum		= rresult[2];
	    }

	    trace += "column='" + column + "', ";
	    switch(column.toLowerCase())
	    {		// act on specific fields
		case 'details':
		{
		    element.onclick	= editVolume;
		    break;
		}

		default:
		{
		    //alert("unexpected column='" + column + "'");
		    break;
		}
	    }		// act on specific fields
	}		// loop through all elements in the form
    }			// loop through all forms
}		// onLoad

/************************************************************************
 *  editVolume								*
 *									*
 *  When a Report button is clicked this function displays the		*
 *  edit dialog for an individual volume.				*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Details....'		*
 ************************************************************************/
function editVolume()
{
    var	form	= this.form;
    var	volume	= this.id.substring(7);
    var	domain	= form.Domain.value;
    location	= 'CountyMarriageReportEdit.php?Domain=' + domain +
			'&Volume=' + volume;
    return false;
}		// editVolume

