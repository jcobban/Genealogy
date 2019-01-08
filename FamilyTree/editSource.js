/************************************************************************
 *  editSource.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editSource.php, which implements the ability to edit			*
 *  details of an event that is recorded in a Source record				*
 *  representing one record in the table tblER.							*
 *																		*
 *  Parameters (passed by method=get):									*
 *		idsr	unique numeric key of instance of Source to edit		*
 *		form	name of form including element to update in caller		*
 *				passed when idsr == 0 to add source to selection		*
 *		select	name of <select> in caller's form to update				*
 *				passed when idsr == 0 to add source to selection		*
 *																		*
 *  History:															*
 *		2010/08/14		created.										*
 *		2010/10/17		support <textarea> elements						*
 *		2011/05/07		improve separation of HTML & Javascript			*
 *		2012/01/13		change class names								*
 *		2013/05/15		support feeding back newly created source info	*
 *						to the invoking page							*
 *						copy source name to source title for new		*
 *						instance										*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2015/02/01		used submit because debug flag was erroneously	*
 *						set												*
 *		2015/02/10		support being opened in <iframe>				*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/06/01		new feedback mechanism to invoking page to		*
 *						supply additional information and move			*
 *						feedback functionality to invoking page.		*
 *						add "Close" button								*
 *		2015/06/16		open picture in other half of the window		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2018/12/02      closing dialog if not in child frame did not    *
 *		                return to Sources menu.                         *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onloadEdit;

/************************************************************************
 *  childFrameClass														*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  function onloadEdit													*
 *																		*
 *  This is the onload method of the page.  Initialize dynamic			*
 *  functionality of elements on the page.								*
 ************************************************************************/
function onloadEdit()
{
    // determine in which half of the window child frames are opened
    if (window.frameElement)
    {				    // dialog opened in half frame
		childFrameClass		= window.frameElement.className;
		if (childFrameClass == 'left')
		    childFrameClass	= 'right';
		else
		    childFrameClass	= 'left';
    }				    // dialog opened in half frame

    // common page initialization
    pageInit();

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {			        // loop through all forms
		var form	= document.forms[i];

		if (form.name == 'srcForm')
		{	            // set action methods for main form
		    form.onsubmit		 	= validateForm;
		    form.onreset 			= resetForm;
		}	            // set action methods for main form

		for(var j = 0; j < form.elements.length; j++)
		{		        // loop through all elements in the form
		    var element	= form.elements[j];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    // take action specific to element
		    var	name;
		    if (element.name && element.name.length > 0)
				name	= element.name;
		    else
				name	= element.id;

		    switch(name)
		    {		    // act on field name
				case 'SrcName':
				{	    // source name input field
				    element.onkeydown	= keyDown;
				    element.onchange	= srcNameChanged;
				    break;
				}	    // source name input field

				case 'updSource':
				{	    // update Source button
				    element.onkeydown	= keyDown;
				    element.onclick	= updSource;
				    break;
				}	    // update Source button

				case 'Close':
				{	    // close dialog button
				    element.onkeydown	= keyDown;
				    element.onclick	= closeDialog;
				    break;
				}	    // close dialog button

				case 'newRepo':
				{	    // new Repository button
				    element.onkeydown	= keyDown;
				    element.onchange	= newRepo;
				    break;
				}	    // new Repository button

				case 'Pictures':
				{		// <button id='Pictures'>
				    element.onclick	= editPictures;
				    break;
				}		// <button id='Pictures'>

				default:
				{
				    element.onkeydown	= keyDown;
				    element.onchange	= change;	// default handler
				    break;
				}
		    }		    // take action on specific element
		}		        // loop through all elements in the form
    }			        // loop through all forms
}		// function onloadEdit

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
}	    // function resetForm

/************************************************************************
 *  function updSource													*
 *																		*
 *  This method is called when the user requests to update				*
 *  a source with the values in the current form.						*
 *																		*
 *  Input:																*
 *		this			<button id='updSource'>							*
 ************************************************************************/
function updSource()
{
    var	form		= this.form;
    var	parms		= {};

    for (var i = 0; i < form.elements.length; i++)
    {		// loop through all form elements
		var	element	= form.elements[i];
		if ((element.tagName == 'INPUT') || (element.tagName == 'TEXTAREA'))
		{		// text input field
		    parms[element.name]	= element.value;
		}		// text input field
		else
		if (element.tagName == 'SELECT')
		{
		    var	io			= element.selectedIndex;
		    if (element.multiple)
		    {		// multiple selection
				// requires serializing the array of values
				parms[element.name]	= element.options[io].value;
		    }		// multiple selection
		    else
		    {		// single selection
				parms[element.name]	= element.options[io].value;
		    }		// single selection
		}
    }		// loop through all form elements
    // invoke script to update Source and return XML result
    HTTP.post('/FamilyTree/updateSource.php',
		      parms,
		      gotSource,
		      noSource);
}	    // function updSource

/************************************************************************
 *  gotSource															*
 *																		*
 *  This method is called when the XML file representing				*
 *  an updated source is retrieved from the database.					*
 *																		*
 *  Input:																*
 *		XML document													*
 ************************************************************************/
function gotSource(xmlDoc)
{
    var	srcForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root.nodeName == 'update')
    {				// database updated
		var	sourceRecords	= root.getElementsByTagName('source');
		if (sourceRecords.length < 1)
		{			// no source record in document
		    alert("editSource.js: gotSource: no <source> element");
		    return;
		}			// no source record in document

		// get instance of Source as an object
		var	sourceRecord	= getParmsFromXml(sourceRecords[0]);
		var	opener	= null;
		if (window.frameElement && window.frameElement.opener)
		    opener	= window.frameElement.opener;

		if (opener)
		{			// invoked as child
		    var	callerDoc		= opener.document;
		    if (args['form'])
		    {			// caller requested feedback
				var	formName		= args['form'];
				sourceRecord.elementname	= args['select'];
				sourceRecord.form		= formName;
				var	form			= callerDoc.forms[formName];
				if (form)
				{			// form found in caller
				    if (form.sourceCreated)
				    {		// call feedback routine
						form.sourceCreated(sourceRecord);
				    }		// call feedback routine
				    else
				    {		// feedback routine not found in caller
						alert("editSource.js: gotSource: " +
						      "Opener <form name='" + formName + 
						    "'> does not have a sourceCreated feedback method");
				    }		// feedback routine not found in caller
				}			// form found in caller
				else
				{			// form not found in caller
				    alert("editSource.js: gotSource: <form name='" + formName +
							"'> not found in calling page");
				}			// form not found in caller
		    }			// caller requested feedback
		    else
		    if (args['elementid'])
		    {			// caller requested feedback
				var	elementId	= args['elementid'];
				sourceRecord.elementid	= elementId;
				var	element		= callerDoc.getElementById(elementId);
				if (element)
				{			// form found in caller
				    if (element.feedback)
				    {		// call feedback routine
						element.feedback(sourceRecord);
				    }		// call feedback routine
				    else
				    {		// feedback routine not found in caller
						alert("editSource.js: gotSource: " +
						      "Opener <element id='" + elementId + 
						    "'> does not have a feedback method");
				    }		// feedback routine not found in caller
				}			// form found in caller
				else
				{			// form not found in caller
				    alert("editSource.js: gotSource: <element id='" + elementId+
							"'> not found in calling page");
				}			// form not found in caller
		    }			// caller requested feedback
		}			// invoked as child

		// hide the window
		closeFrame();
    }				// database updated
    else
    {				// error in response
		var	msg	= "Error: ";
		for(var i = 0; i < root.childNodes.length; i++)
		{			// loop through children
		    var node	= root.childNodes[i];
		    if (node.nodeValue != null)
				msg	+= node.nodeValue;
		}			// loop through children
		alert (msg);
    }				// error in response
}		// function gotSource

/************************************************************************
 *  function noSource													*
 *																		*
 *  This method is called if there is no event							*
 *  file.																*
 ************************************************************************/
function noSource()
{
    alert('Script updateSource.php not found on server.');
}		// function noSource

/************************************************************************
 *  function closeDialog												*
 *																		*
 *  This method is called when the user requests to close the			*
 *  dialog without updating the database.								*
 *																		*
 *  Input:																*
 *		this			<button id='Close'>								*
 ************************************************************************/
function closeDialog()
{
    closeFrame();
}		// function closeDialog

/************************************************************************
 *  function newRepo													*
 *																		*
 *  This method is called when the user requests to add a new			*
 *  repository address. The program displays a dialog to initialize		*
 *  a new instance of Address.											*
 *																		*
 *  Input:																*
 *		this			<input id='newRepo'>							*
 ************************************************************************/
function newRepo()
{
    if (this.value.length > 0)
    {			// have name of the new repository
		var 	url	="/FamilyTree/Address.php?idar=0&kind=2&name=" +
						  encodeURIComponent(this.value);
		openFrame("repo",
				  url,
				  childFrameClass);
    }			// have name of the new repository
    return false;
}	    // function newRepo

/************************************************************************
 *  function srcNameChanged												*
 *																		*
 *  This method is called when the user modifies the value of the		*
 *  SrcName input field.												*
 *																		*
 *  Input:																*
 *		this			<input name='SrcName'>							*
 ************************************************************************/
function srcNameChanged()
{
    var	form	= this.form;
    if (form.SrcTitle)
    {
		if (form.SrcTitle.value == '')
		    form.SrcTitle.value	= this.value;
    }
}	    // function srcNameChanged

/************************************************************************
 *  function editPictures												*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures associated with the source			*
 *  that are recorded by instances of Picture.							*
 *																		*
 *  Parameters:															*
 *		this		a <button> element									*
 ************************************************************************/
function editPictures()
{
    var	form		= this.form;
    var	picIdType	= form.PicIdType.value;
    var	idsr;

    if (form.idsr && form.idsr.value > 0)
    {		// idsr present in form
		idsr		= form.idsr.value;
		openFrame("pictures",
				  "/FamilyTree/editPictures.php?idsr=" + idsr + 
									"&idtype=" + picIdType, 
				  childFrameClass);
    }		// idsr present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	    // function editPictures

