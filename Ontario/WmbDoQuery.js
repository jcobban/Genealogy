/************************************************************************
 *  WmbDoQuery.js							*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  WmbDoQuery.html							*
 *									*
 *  History:								*
 *	2016/02/20	created						*
 *									*
 *  Copyright &copy; 2016 James A. Cobban.				*
 ************************************************************************/

window.onload	= onLoad

/************************************************************************
 *  onLoad								*
 *									*
 *  Put the input focus on the next page hyperlink so the user can	*
 *  scroll through multi-page results just by pressing the enter key.	*
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

	    if (element.id.substring(0, 7) == 'Details')
	    {
		element.helpDiv	= 'Details';
		element.onclick	= showReg;
	    }
	}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  showReg								*
 *									*
 *  When a Action button is clicked this function displays the		*
 *  page to edit or display details of the registration.		*
 *									*
 *  Input:								*
 *	this	<button type=button id='Details...'>			*
 ************************************************************************/
function showReg()
{
    var	form	= this.form;
    var	recid	= this.id.substring(7);
    // display details
    window.open('WmbDetail.php?IDMB=' + recid);
    return false;
}		// showReg

