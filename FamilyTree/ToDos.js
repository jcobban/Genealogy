/************************************************************************
 *  ToDos.js														    *
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page ToDos.php.													    *
 *																		*
 *  History:															*
 *		2019/08/13      created                                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onloadToDos;

/************************************************************************
 *  function childFrameClass											*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function onLoadToDos											*
 *																		*
 *  Initialize dynamic functionality of page.							*
 ************************************************************************/
function onloadToDos()
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

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];

		if (form.name == 'locForm')
		{	// locForm
		    form.onsubmit	= validateForm;
		    form.onreset 	= resetForm;
		}	// locForm

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements
		    var element	= form.elements[j];

		    // take action depending upon the element name
		    var	name;
		    if (element.name && element.name.length > 0)
				name	= element.name;
		    else
				name	= element.id;

		    switch(name)
		    {		// act on specific element
				case 'pattern':
				{
				    element.onkeydown	= keyDown;
				    element.onchange	= patternChanged;
				    element.focus();
				    break;
				}

				case 'Search':
				{
				    element.onclick	    = search;
				    break;
				}

				case 'Close':
				{
				    element.onclick	    = closeDialog;
				    break;
				}

				case 'New':
				{
				    element.onclick	    = newToDo;
				    break;
				}

				default:
				{
				    element.onkeydown	= keyDown;
				    element.onchange	= change;	// default handler
				    break;
				}
		    }		// act on specific element
		}       	// loop through elements in form
    }	        	// iterate through all forms

    // add mouseover actions for forward and backward links
    var npprev	= document.getElementById('topPrev');
    if (npprev)
    {		// defined
		npprev.onmouseover	= linkMouseOver;
		npprev.onmouseout	= linkMouseOut;
    }		// defined
    var npnext	= document.getElementById('topNext');
    if (npnext)
    {		// defined
		npnext.onmouseover	= linkMouseOver;
		npnext.onmouseout	= linkMouseOut;
    }		// defined

}		// onLoadToDos

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  function patternChanged												*
 *																		*
 *  Take action when the value of the pattern field changes.  This		*
 *  specifically means that changes have been made and the focus has	*
 *  then left the field.												*
 *																		*
 *  Input:																*
 *		this			<input type='text' id='pattern'>				*
 ************************************************************************/
function patternChanged()
{
    var	form	= this.form;

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);
    else
    if (this.value == '[')
		this.value	= '[Blank]';

    form.submit();
}		// patternChanged

/************************************************************************
 *  function search														*
 *																		*
 *  Take action to either submit the form or pop up a dialog to create	*
 *  or edit a specific todo item.										*
 *																		*
 *  Input:																*
 *		this			<button id='Search'>							*
 ************************************************************************/
function search()
{
    var	form	= this.form;
	form.submit();
}		// search

/************************************************************************
 *  function closeDialog												*
 *																		*
 *  Take action to close the dialog.									*
 *																		*
 *  Input:																*
 *		this			<button id='Close'>								*
 ************************************************************************/
function closeDialog()
{
    closeFrame();
}		// closeDialog

/************************************************************************
 *  function newToDo													*
 *																		*
 *  Create a new todo item using the pattern or name					*
 *																		*
 *  Input:																*
 *		this			<button id='New'>								*
 ************************************************************************/
function newToDo()
{
    var form	    = this.form;
	var url	        = "ToDo.php";
    if (form.idir.value.length > 0)
		url	        = "ToDo.php?idir=" + form.idir.value;
    openFrame('todoitem', url, childFrameClass)
}		// newToDo

/************************************************************************
 *  function linkMouseOver												*
 *																		*
 *  This function is called if the mouse moves over a forward or		*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this			element the mouse moved on to					*
 ************************************************************************/
function linkMouseOver()
{
    var	msgDiv	= document.getElementById('mouse' + this.id);
    if (msgDiv)
    {		// support for dynamic display of messages
		// display the messages balloon in an appropriate place on the page
		var leftOffset		= getOffsetLeft(this);
		if (leftOffset > 500)
		    leftOffset	    -= 200;
		msgDiv.style.left	= leftOffset + "px";
		msgDiv.style.top	= (getOffsetTop(this) - 30) + 'px';
		show(msgDiv);

		// so key strokes will close window
		helpDiv		    	= msgDiv;
		helpDiv.onkeydown	= keyDown;
    }		// support for dynamic display of messages
}		// linkMouseOver

/************************************************************************
 *  function linkMouseOut												*
 *																		*
 *  This function is called if the mouse moves off a forward or			*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this			element the mouse moved off of					*
 ************************************************************************/
function linkMouseOut()
{
    if (helpDiv)
    {
		helpDiv.style.display	= 'none';
		helpDiv			= null;
    }
}		// linkMouseOut

