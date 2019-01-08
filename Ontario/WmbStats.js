/**
 *  WmbStats.js
 *
 *  This file implements the dynamic functionality of the web page
 *  WmbStats.php
 *
 *  History:
 *	2013/06/29	created
 *	2013/08/01	defer facebook initialization until after load
 *
 *  Copyright &copy; 2013 James A. Cobban.
 **/

window.onload	= loadStats;

/**
 *  loadStats
 *
 *  Perform dynamic initialization for the page
 **/
function loadStats()
{
    pageInit();

    // define dynamic actions and
    // support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
	var form	= document.forms[i];
	for(var j = 0; j < form.elements.length; j++)
	{	// loop through all elements of a form
	    var element		= form.elements[j];

	    element.onkeydown	= keyDown;

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    if (element.parentNode.nodeName == 'TD')
	    {		// set mouseover on containing cell
		element.parentNode.onmouseover	= eltMouseOver;
		element.parentNode.onmouseout	= eltMouseOut;
	    }		// set mouseover on containing cell
	    else
	    {		// set mouseover on input element itself
		element.onmouseover		= eltMouseOver;
		element.onmouseout		= eltMouseOut;
	    }		// set mouseover on input element itself

	    // an element whose value is passed with the update
	    // request to the server is identified by a name= attribute
	    // but elements which are used only by this script are
	    // identified by an id= attribute
	    var	name	= element.name;
	    if (name.length == 0)
		name	= element.id;

	    // set up dynamic functionality based on the name of the element
	    if (name.substring(0, 17) == "ShowDistrictStats")
	    {
		element.onclick	= showStatus;
		element.helpDiv	= "ShowDistrictStats";
	    }
	}	// loop through all elements in the form
    }		// loop through forms in the page
}		// loadStats

/**
 *  showStatus
 *
 *  onclick method for a district status button.
 *  Switch to the transcription status page for the specific district.
 **/
function showStatus()
{
    location	= "WmbDistrictStats.php?district=" + encodeURIComponent(this.id.substring(17));
    return false;	// suppress default action
}		// showStatus

