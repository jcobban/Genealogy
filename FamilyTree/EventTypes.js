/************************************************************************
 *  EventTypes.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page EventTypes.php.												*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/10/14		add mouseover help								*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/07/31		defer setup of facebook link					*
 *		2015/06/16		open details in other half of the window		*
 *						simplify passing IDET to button handlers		*
 *		2019/07/23      update to new implementation                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  childFrameClass														*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onLoad()
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
    for(var fi = 0; fi < document.forms.length; fi++)
    {
		form		= document.forms[fi];
		if (form.name == 'srcForm')
		{	// set action methods for form
		    form.onsubmit		 	= validateForm;
		    form.onreset 			= resetForm;
		}	// set action methods for form
    
		// activate handling of key strokes in text input fields
		// including support for context specific help
		var formElts		= form.elements;
		for (var ei = 0; ei < formElts.length; ++ei)
		{		// loop through all elements in the form
		    var element		= formElts[ei];
		    element.onkeydown	= keyDown;
		    element.onchange	= relChange;
		    var	name		= element.name;
		    if (name == '')
				name		= element.id;
		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    if (name == 'Add')
				element.onclick	= addEventType;
		    else
		    if (name.substring(0, 6) == 'Delete')
            {
				element.onclick	= delEventType;
                if (name == 'Delete1')
                    element.disabled    = true;
            }
		    else
		    if (name.substring(0, 4) == 'More')
				element.onclick	= moreEventType;
		}		// loop through all elements in the form
    }			// form defined
}		// function onLoad

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// function validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function relChange													*
 *																		*
 *  This method is called when the user modifies the value of			*
 *  a field.															*
 *																		*
 *  Parameters:															*
 *		this		<input type='text'>									*
 ************************************************************************/
function relChange()
{
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    var	cell1		= row.cells[0];
    var inputList	= cell1.getElementsByTagName('INPUT');
    var	idcpElt		= null;
    for (var i = 0; i < inputList.length; i++)
    {			// loop through child <input> elements
		if (inputList[i].name.substring(0,7) == 'Updated')
		{		// element tracks changes in this row
		    var chgElt	= inputList[i];
		    chgElt.value= 1;
		    break;
		}		// element tracks changes in this row
    }			// loop through child <input> elements
}		// function relChange

/************************************************************************
 *  function addEventType												*
 *																		*
 *  This method is called when the user requests to create				*
 *  a new EventType.													*
 *																		*
 *  Input:																*
 *		this		<button id='Add...'>								*
 ************************************************************************/
function addEventType(ev)
{
    ev.stopPropagation();
    var	form		= this.form;
    var	table		= document.getElementById('formTable');
    var tbody		= table.tBodies[0];
    var newrownum	= tbody.rows.length;
    var newidet		= 1;
    for(var ie = 0; ie < form.elements.length; ie++)
    {				// loop through all form elements
		var element	= form.elements[ie];
		if (element.name.substring(0,4) == 'IDET')
		{			// IDET value
		    var idet	= element.value - 0;
		    if (idet >= newidet)
				newidet	= idet + 1;
		}			// IDET value
    }				// loop through all form elements

    var	rowTemplate = document.getElementById('newEvent$rownum');
    var	parms	= {'rownum'	: newidet,
				   'idet'	: newidet,
				   'eventtype'	: 'Fill in the Blanks'};
    var	newrow	= createFromTemplate(rowTemplate,
								     parms,
								     null);
    tbody.appendChild(newrow);
}	// function addEventType

/************************************************************************
 *  function delEventType												*
 *																		*
 *  This method is called when the user requests to delete				*
 *  an existing EventType.												*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'>								*
 ************************************************************************/
function delEventType(ev)
{
    ev.stopPropagation();
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;

    for(var ic = 0; ic < row.cells.length; ic++)
    {			// loop through cells of current row
		var	cell1		= row.cells[ic];
		var	oldidcp		= 1;
		var	inputList	= cell1.getElementsByTagName('INPUT');
		for (var ii = 0; ii < inputList.length; ii++)
		{			// loop through child <input> elements
		    var element		= inputList[ii];
		    if (element.type.toLowerCase() == 'checkbox')
		    {
				element.checked	= false;
		    }
		    else
		    if (element.type.toLowerCase() == 'text')
		    {
				element.value	= '';
		    }
		}			// loop through child <input> elements
    }			// loop through cells of current row
}		// function delEventType

/************************************************************************
 *  function moreEventType												*
 *																		*
 *  This method is called when the user requests to see more			*
 *  information on an existing EventType.								*
 *																		*
 *  Input:																*
 *		this		<button id='More...'>								*
 ************************************************************************/
function moreEventType(ev)
{
    ev.stopPropagation();
    var	idet		= this.id.substring(4);
    openFrame("event",
		      'editEventType.php?idet=' + idet,
		      childFrameClass);
    return false;
}		// function moreEventType
