/************************************************************************
 *  Temple.js																*
 *																		*
 *  Javascript code to implement dynamic functionality of the				*
 *  page Temple.php.														*
 *																		*
 *  History:																*
 *		2012/12/06		created												*
 *		2013/05/29		use actMouseOverHelp common function				*
 *		2013/08/01		defer facebook initialization until after load		*
 *		2015/06/16		open list of pictures in other half				*
 *						of the window										*
 *		2015/12/08		remove alert										*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onLoadTemple;

/************************************************************************
 *  function childFrameClass												*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs		*
 *  are opened in the other half of the window.								*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function onLoadTemple												*
 *																		*
 *  This function is invoked once the page is completely loaded into		*
 *  the browser.  Initialize dynamic behavior of elements.				*
 ************************************************************************/
function onLoadTemple()
{
    // determine in which half of the window child frames are opened
    if (window.frameElement)
    {				// dialog opened in half frame
		childFrameClass		= window.frameElement.className;
		if (childFrameClass == 'left')
		    childFrameClass	= 'right';
		else
		    childFrameClass	= 'left';
    }				// dialog opened in half frame

    var	form				= document.locForm;

    // set action methods for form
    form.onsubmit		 	= validateForm;
    form.onreset 			= resetForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var element		= formElts[i];
		element.onkeydown	= keyDown;

		// take action specific to element
		var	name	= element.name;
		if (name.length == 0)
		    name	= element.id;
		switch(name.toLowerCase())
		{		// switch on field name
		    case 'code':
		    {
				element.onchange	= change;	// default handler
				element.checkfunc	= checkTCode;
				break;
		    }

		    case 'temple':
		    {
				element.onchange	= changeTemple;
				break;
		    }

		    case 'templestart':
		    case 'templeend':
		    {
				element.onchange	= change;	// default handler
				element.checkfunc	= checkYyyymmdd;
				break;
		    }

		    case 'references':
		    {			// <button id='References'>
				element.onclick	= displayReferences;
				break;
		    }			// <button id='References'>

		    case 'pictures':
		    {		// <button id='Pictures'>
				element.onclick	= editPictures;
				break;
		    }		// <button id='Pictures'>
		    
		    default:
		    {
				element.onchange	= change;	// default handler
				break;
		    }
		}		// switch on field name

    }		// loop through all elements in the form
}		// onLoadTemple

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm														*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  function changeTemple												*
 *																		*
 *  Handle a change to the value of the Temple field.						*
 *																		*
 *  Input:																*
 *		this is the Temple input text element								*
 ************************************************************************/
function changeTemple()
{
    var	form		= this.form;

    // the following code is from CommonForm.js function change
    // trim off leading and trailing spaces
    this.value		= this.value.trim();

    // if the form has a button named Submit, enable it just in case
    // it was previously disabled
    if (form.Submit)
		form.Submit.disabled	= false;

    // capitalize words in value if presentation style requires it
    var textTransform	= "";
    if (this.currentStyle)		// try IE API
		textTransform	= this.currentStyle.textTransform;
    else
    if (window.getComputedStyle)	// W3C API
		textTransform	= window.getComputedStyle(this, null).textTransform;
    if (textTransform == "capitalize")
		capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);
    else
    if (this.value == '[')
		this.value	= '[Blank]';

}		// changeTemple

/************************************************************************
 *  function checkTCode														*
 *																		*
 *  Validate the current value of a field containing a temple				*
 *  abbreviation.														*
 *																		*
 *  Input:																*
 *	this		<input name='code'>				* 
 ************************************************************************/
function checkTCode()
{
    var	element		= this;
    var	re		= /^[A-Z]{5}$/;
    var	date		= element.value;
    var	className	= element.className;
    var	result	= re.exec(date);
    var	matched	= typeof result === 'object' && result instanceof Array;

    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the error flag
		if (matched)
		{
		    element.className	= className.substring(0, className.length - 5);
		}
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add 'error' to class name
		if (!matched)
		    element.className	= element.className + "error";
    }		// error not currently flagged
}		// checkTCode

/************************************************************************
 *  function checkYyyymmdd												*
 *																		*
 *  Validate the current value of a field containing a date.				*
 *																		*
 *  Input:																*
 *		this				<input name=templestart'> or						*
 *			<input name='templeend'>
 ************************************************************************/
function checkYyyymmdd()
{
    var	element		= this;
    var	re		= /^[0-9]{8}$/;
    var	date		= element.value;
    var	className	= element.className;
    var	result	= re.exec(date);
    var	matched	= typeof result === 'object' && result instanceof Array;

    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the error flag
		if (matched)
		{
		    element.className	= className.substring(0, className.length - 5);
		}
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add 'error' to class name
		if (!matched)
		    element.className	= element.className + "error";
    }		// error not currently flagged
}		// checkYyyymmdd

/************************************************************************
 *  function displayReferences												*
 *																		*
 *  This function is called if the user clicks on the references button.*
 *  It displays a list of records that reference this temple				*
 *																		*
 *  Input:																*
 *		this				 <button id='References'>						*
 ************************************************************************/
function displayReferences()
{
    var	form		= document.locForm;
    var	idlr		= form.idlr.value;
    temple		= 'getIndividualsByTemple.php?idlr=' + idlr;
    return false;
}		// displayReferences

/************************************************************************
 *  function editPictures												*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures associated with the source			*
 *  that are recorded by instances of Picture.							*
 *																		*
 *  Parameters:															*
 *		this		a <button id='Pictures'> element					*
 ************************************************************************/
function editPictures()
{
    var	form		= this.form;
    var	picIdType	= form.PicIdType.value;
    var	idtr;

    if (form.idtr && form.idtr.value > 0)
    {		// idtr present in form
		idtr		= form.idtr.value;
		openFrame("pictures",
				  "editPictures.php?idtr=" + idtr +
						    "&idtype=" + picIdType, 
				  childFrameClass);
    }		// idtr present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Temple.js: editPictures: " +
				   "Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// editPictures
