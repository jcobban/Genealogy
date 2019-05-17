/************************************************************************
 *  WmbResponse.js														*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  WmbResponse.php														*
 *																		*
 *  History:															*
 *		2016/02/20		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04.28      renamed to WmbResponse.js                       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.								*
 ************************************************************************/

window.onload	= onLoad

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Put the input focus on the next page hyperlink so the user can		*
 *  scroll through multi-page results just by pressing the enter key.	*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    var	element;

    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    if (element.id.substring(0, 7) == 'Details')
		    {
				element.helpDiv	= 'Details';
				element.onclick	= showReg;
		    }
		}	// loop through all elements in the form
    }		// loop through all forms

}		// onLoad

/************************************************************************
 *  function showReg													*
 *																		*
 *  When a Action button is clicked this function displays the			*
 *  page to edit or display details of the registration.				*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Details...'>				*
 ************************************************************************/
function showReg()
{
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    var	form	= this.form;
    var	recid	= this.id.substring(7);
    // display details
    window.open('WmbDetail.php?IDMB=' + recid + '&lang=' + lang);
    return false;
}		// showReg

