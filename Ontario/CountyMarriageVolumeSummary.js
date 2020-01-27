/************************************************************************
 *  CountyMarriageVolumeSummary.js										*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountyMarriageVolumeSummary.php										*
 *																		*
 *  History:															*
 *		2017/07/15		created											*
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

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    element.onkeydown	= keyDown;

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
		}		    // loop through all elements in the form
    }			    // loop through all forms
    var volhead         = document.getElementById('volhead');
    var volfoot         = document.getElementById('volfoot');
    if (volhead)
    {
        volfoot.style.width     = volhead.offsetWidth + 'px';
    }
}		// function onLoad

/************************************************************************
 *  function editVolume													*
 *																		*
 *  When a Report button is clicked this function displays the			*
 *  edit dialog for an individual volume.								*
 *																		*
 *  Input:																*
 *		$this		<button type=button id='Details....'				*
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

