/************************************************************************
 *  UpdateAddress.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page UpdateAddress.php.												*
 *																		*
 *  History:															*
 *	    2015/05/27	    created                                         *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onLoadAddress;

/************************************************************************
 *  function onLoadAddress												*
 *																		*
 *  Function invoked once the web page is loaded into the browser.		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function onLoadAddress()
{
    var	form			= document.locForm;
    var formName		= '';
    var msglengths		= 0;
    var idar			= 0;

    var	opener	= null;
    if (window.frameElement && window.frameElement.opener)
	opener	= window.frameElement.opener;
    else
	opener	= window.opener;

    // set action methods for elements
    form.onsubmit	 	= ignoreSubmit;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
	var element	= formElts[i];

	var	name;
	if (element.name && element.name.length > 0)
	    name	= element.name;
	else
	    name	= element.id;

	switch(name)
	{		// act on field name
	    case 'formname':
	    {		// act on form name value
		formName		= element.value;
		break;
	    }		// act on form name value

	    case 'idar':
	    {		// act on IDAR value
		idar			= element.value;
		if (formName.length > 0 && opener)
		{
		    var callersForm	= opener.document.forms[formName];
		    if (callersForm)
		    {
			callersForm.setIdar(idar);
		    }		// have callers form
		}		// invoked from another window with a form name
		break;
	    }		// act on IDAR value

	    case 'msglengths':
	    {
		msglengths		= element.value;
		break;
	    }

	    case 'Close':
	    {		// <button id='Close'>
		element.onclick	= closeDialog;
		break;
	    }		// <button id='Close'>

	}		// act on field name
    }			// loop through all elements in the form

    // if there were no messages to display to the user, just close the dialog
    if (msglengths == 0)
	closeFrame();
}		// function onLoadAddress

/************************************************************************
 *  function ignoreSubmit												*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this		instance of <form>										*
 ************************************************************************/
function ignoreSubmit()
{
    closeFrame();
    return false;
}		// function validateForm

/************************************************************************
 *  function closeDialog												*
 *																		*
 *  This method closes the frame without updating the record.			*
 *																		*
 *  Input:																*
 *		this		instance of <button id='Close'>								*
 ************************************************************************/
function closeDialog()
{
    closeFrame();
    return false;
}		// function closeDialog

