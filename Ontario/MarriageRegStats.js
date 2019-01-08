/**
 *  MarriageRegStats.js
 *
 *  This file implements the dynamic functionality of the web page
 *  MarriageRegStats.php
 *
 *  History:
 *	2011/00/27	created
 *	2013/08/01	defer facebook initialization until after load
 *
 *  Copyright &copy; 2013 James A. Cobban
 **/

window.onload	= onLoad;

/**
 *  onLoad
 *
 *  Initialize the dynamic functionality once the page is loaded
 **/
function onLoad()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    for (fi = 0; fi < document.forms.length; fi++)
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
    
	    if (element.id.substring(0, 9) == 'YearStats')
	    {
		element.helpDiv	= 'YearStats';
		element.onclick	= showYearStats;
	    }
	}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/**
 *  showYearStats
 *
 *  When a YearStats button is clicked this function displays the
 *  statistics for specific year.
 **/
function showYearStats()
{
    var	rownum	= this.id.substring(9);
    var year	= document.getElementById('RegYear' + rownum).value;
    location	= 'MarriageRegYearStats.php?regyear=' + year;
    return false;
}		// showYearStats
