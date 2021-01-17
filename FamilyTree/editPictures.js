/************************************************************************
 *  editPictures.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editPictures.php which displays the list of pictures			*
 *  associated with a genealogical record such as an individual.		*
 *																		*
 *  History:															*
 *		2011/05/26		created											*
 *		2012/01/13		change class names								*
 *		2013/02/24		support mouseover help							*
 *						field names changed to use IDBR not rownum		*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2014/03/06		increase width of dialog window for editPicture	*
 *						standardize alert message text					*
 *		2014/03/21		name of delete script changed					*
 *		2014/10/04		prompt for confirmation before deleting	picture	*
 *						after deleting remove row from display rather	*
 *						than refreshing the dialog						*
 *						display message returned from failed delete		*
 *		2015/02/10		hide window if in <iframe>						*
 *						use closeFrame									*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/06/16		open picture in other half of the window		*
 *		2016/02/06		call pageInit on load							*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/19      call element.click to trigger button click      *
 *		2019/06/29      first parameter of displayDialog removed        *
 *		2020/02/17      hide right column                               *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= loadEdit;

/************************************************************************
 *  function childFrameClass												*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs		*
 *  are opened in the other half of the window.								*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function loadEdit														*
 *																		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function loadEdit()
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

    document.body.onkeydown	= epKeyDown;
    var	form			= document.picsForm;

    // set action methods for elements
    form.onsubmit 	= validateForm;
    form.onreset 	= resetForm;

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
		{
		    case 'Add':
		    {
                element.addEventListener('click', picAdd);
				break;
		    }

		    case 'Close':
		    {
                element.addEventListener('click', finish);
				element.focus();
				break;
		    }

		    case 'Order':
		    {
                element.addEventListener('click', orderByDate);
				break;
		    }

		    default:
		    {
				if (name.substring(0,4) == 'Edit')
				    element.onclick	= picEdit;
				else
				if (name.substring(0,3) == 'Del')
				    element.onclick	= picDel;
				else
				{
				    element.onkeydown	= keyDown;
				    element.onchange	= change;	// default handler
				}
				break;
		    }

		}	// switch on element name
    }		// loop through all elements in the form

    hideRightColumn();
}		// function loadEdit

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this				instance of <form>								*
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
 *																		*
 *  Input:																*
 *		this				instance of <form>								*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function picEdit													*
 *																		*
 *  This method is called when the user requests to edit				*
 *  a picture of a genealogical entity.									*
 *																		*
 *  Input:																*
 *		this			instance of <button id='Edit...'>				*
 *		ev              instance of Event                               *
 ************************************************************************/
function picEdit(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form		= this.form;
    var	idbr		= this.id.substring(4);
    openFrame("picture",
		      "editPicture.php?idbr=" + idbr,
		      childFrameClass);
    return true;
}	// function picEdit

/************************************************************************
 *  function picAdd														*
 *																		*
 *  This method is called when the user requests to add					*
 *  a picture to a genealogical entity.									*
 *																		*
 *  Input:																*
 *		this			instance of <button id='Add'>					*
 *		ev              instance of Event                               *
 ************************************************************************/
function picAdd(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form		= this.form;
    var	idir		= form.idir.value;
    var	idtype		= form.idtype.value;
    openFrame("picture",
		      "editPicture.php?idbr=0&idir=" + idir + "&idtype=" + idtype, 
		      childFrameClass);
    return true;
}	// function picAdd

/************************************************************************
 *  function picDel														*
 *																		*
 *  This method is called when the user requests to delete				*
 *  a picture from a genealogical entity.								*
 *																		*
 *  Input:																*
 *		this			instance of <button id='Del...'>				*
 *		ev              instance of Event                               *
 ************************************************************************/
function picDel(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form		= this.form;
    var	idbr		= this.id.substring(3);
    var parms		= {"idbr"	    : idbr,
					   "rownum"	    : idbr,
					   "formname"	: form.name, 
					   "template"	: ""};

    if (debug != 'n')
		parms["debug"]	= debug;

    // ask user to confirm delete
	displayDialog('PicDel$template',
			      parms,
			      this,		        // position relative to
			      confirmDelete);	// 1st button confirms Delete
}		// function picDel

/************************************************************************
 *  function confirmDelete												*
 *																		*
 *  This method is called when the user confirms the request to delete	*
 *  a picture.															*
 *																		*
 *  Input:																*
 *		this			<button id='confirmDelete...'>					*
 *		ev              instance of Event                               *
 ************************************************************************/
function confirmDelete(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	idbr		= this.id.substr(13);
    var	rownum		= form.elements['rownum' + idbr].value;
    var	formname	= form.elements['formname' + idbr].value;
    var parms		= { "idbr" : idbr };

    if (debug != 'n')
		parms["debug"]	= debug;

    // hide the dialog
    dialogDiv.style.display	= 'none';

    // run AJAX script to delete the record
    HTTP.post('/FamilyTree/deletePictureXml.php',
		      parms,
		      gotDelete,
		      noDelete);
}	// function confirmDelete

/************************************************************************
 *  function gotDelete													*
 *																		*
 *  This method is called when the response to the request to delete	*
 *  a picture is received from the server.								*
 *																		*
 *  Parameters:															*
 *		xmlDoc			reply as an XML document						*
 ************************************************************************/
function gotDelete(xmlDoc)
{
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'deleted')
    {
		var children	= root.childNodes;
		var parms	= null;
		var msg		= null;
		for (i = 0; i < children.length; i++)
		{		// loop through 1st level children
		    var child	= children[i];
		    if (child.nodeType == 1)
		    {		// tag
				if (child.nodeName == 'parms')
				{
				    parms	= child;
				}
				else
				if (child.nodeName == 'msg')
				    msg		= child;
		    }		// tag
		}		// loop through 1st level children

		var	idbr	= null;
		if (parms)
		for (i = 0; i < parms.childNodes.length; i++)
		{		// loop through 1st level children
		    var parm	= parms.childNodes[i];
		    if (parm.nodeType == 1)
		    {		// tag
				if (parm.nodeName == 'idbr')
				    idbr	= parm.textContent;
		    }		// tag
		}		// loop through 1st level children

		var	button	= null;
		if (idbr !== null)
		    button	= document.getElementById('Del' + idbr);
		
		if (msg)
		    popupAlert(msg, button);
		else
		{		// picture deleted, remove row
		    var row	= document.getElementById('PictureRow' + idbr);
		    var	sect	= row.parentNode;
		    sect.removeChild(row);
		}		// picture deleted
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root && root.childNodes)
		    msg	+= new XMLSerializer().serializeToString(root)
		else
		    msg	+= xmlDoc;
		alert (msg);
    }		// error
}	// function gotDelete

/************************************************************************
 *  function noDelete													*
 *																		*
 *  This method is called if there is no response to the AJAX			*
 *  delete picture request.												*
 ************************************************************************/
function noDelete()
{
    alert("editPictures.js: noDelete: " +
		"script deletePictureXml.php not found on server");
}	// function noDelete

/************************************************************************
 *  function orderByDate												*
 *																		*
 *  This method is called when the user requests to reorder the			*
 *  pictures by date.													*
 *																		*
 *  Input:																*
 *		this			instance of <button id='Order'>					*
 *		ev              instance of Event                               *
 ************************************************************************/
function orderByDate(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form		= this.form;
    var	idir		= form.idir.value;
    var	idtype		= form.idtype.value;
    var parms		= { "idir" : idir,
					    "idtype" : idtype
					  };

    // submit an AJAX request to update the database
    HTTP.post('/FamilyTree/orderPicturesByDateXml.php',
		      parms,
		      gotOrder,
		      noOrder);
}	// function orderByDate

/************************************************************************
 *  function gotOrder													*
 *																		*
 *  This method is called when the XML response to a request to reorder	*
 *  the pictures by date is received.									*
 *																		*
 *  Parameters:															*
 *		xmlDoc			reply as an XML document						*
 ************************************************************************/
function gotOrder(xmlDoc)
{
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName == 'ordered')
    {
		window.location	= window.location;	// refresh window
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root)
		{
		    for(var i = 0; i < root.childNodes.length; i++)
		    {		// loop through children
				var node	= root.childNodes[i];
				if (node.nodeValue != null)
				    msg		+= node.nodeValue;
		    }		// loop through children
		}		// have XML response
		else
		    msg		+= xmlDoc;
		alert (msg);
    }		// error
}	// function gotOrder

/************************************************************************
 *  function noOrder													*
 *																		*
 *  This method is called if there is no response to the AJAX			*
 *  reorder pictures call.												*
 ************************************************************************/
function noOrder()
{
    alert("editPictures.js: noOrder: " +
		"script orderPicturesByDateXml.php not found on server");
}	// function noOrder

/************************************************************************
 *  function finish														*
 *																		*
 *  This method is called when the user requests to close				*
 *  the window.															*
 *																		*
 *  Input:																*
 *		this			instance of <button id='Close'>					*
 *		ev              instance of Event                               *
 ************************************************************************/
function finish(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    closeFrame();
    return true;
}	// function finish

/************************************************************************
 *  function epKeyDown													*
 *																		*
 *  The key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Citation button.												*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function epKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.picsForm;

    if (e.ctrlKey)
    {		// ctrl
		if (code == 83)
		{	// ctrl-S
		    window.close();
		    return false;	// do not perform default action
		}	// ctrl-S
    }		// ctrl

    if (e.altKey)
    {		// alt
		// take action based upon code
		switch (code)
		{	// switch on key code
		    case 65:
		    {		// alt-A
				document.getElementById('Add').click();
				return false;
		    }		// alt-A

		    case 67:
		    {		// alt-C
				window.close();
				return false;
		    }		// alt-C

		    case 79:
		    {		// alt-O
				document.getElementById('Order').click();
				return false;
		    }		// alt-O

		}	// switch on key code
    }		// alt

    return true;
}		// function epKeyDown

