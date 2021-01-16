/************************************************************************
 *  ToDo.js																*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page ToDo.php.														*
 *																		*
 *  History:															*
 *		2019/08/14		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.addEventListener('load', onLoadToDo);

/************************************************************************
 *  function childFrameClass											*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function onLoadToDo												    *
 *																		*
 *  This function is invoked once the page is completely loaded into	*
 *  the browser.  Initialize dynamic behavior of elements.				*
 ************************************************************************/
function onLoadToDo()
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

    var	form				= document.getElementById('todoForm');

    // set action methods for form
    form.addEventListener('submit', validateForm);
    form.addEventListener('reset', resetForm);

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var element		= formElts[i];
		element.addEventListener('keydown', keyDown);

		// take action specific to element
		var	name	= element.name;
		if (name.length == 0)
		    name	= element.id;
		switch(name.toLowerCase())
		{		// switch on field name
            case 'todoname':
            {
				element.addEventListener('change', changeToDoName);
				break;
            }

		    case 'pictures':
		    {		// <button id='Pictures'>
				element.addEventListener('click', editPictures);
				break;
		    }		// <button id='Pictures'>
		    
		    default:
		    {
				element.addEventListener('change', change);	// default handler
				break;
		    }
		}		// switch on field name

    }		// loop through all elements in the form
}		// function onLoadToDo

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// function validateForm

/************************************************************************
 *  function resetForm														*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function changeToDoName												*
 *																		*
 *  Handle a change to the value of the ToDoName field.					*
 *																		*
 *  Input:																*
 *		this is the ToDoName input text element							*
 ************************************************************************/
function changeToDoName(ev)
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
    if (this.currentStyle)		    // try IE API
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

}		// function changeToDoName

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
 *		ev          W3C compliant browsers pass instance of Event       *
 ************************************************************************/
function editPictures(ev)
{
    ev.stopPropagation();

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
		popupAlert("ToDo.js: editPictures: " +
				   "Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// function editPictures
