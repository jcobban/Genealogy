/************************************************************************
 *  editCitation.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editCitation.php.												*
 *																		*
 *  History:															*
 *		2010/10/15		some methods referred to form id='indForm'		*
 *						sourceClick method correct field name			*
 *		2011/02/27		improve separation of HTML and Javascript		*
 *						feedback change in source and page fields to	*
 *						citTable in invoking page						*
 *		2011/03/19		short-cut keys for submit						*
 *		2012/01/13		change class names								*
 *		2012/02/25		use tinyMCE to edit extended text notes			*
 *		2013/03/31		support multiple citation tables in invoking	*
 *						page through new formId parameter				*
 *		2013/05/29		activate popup help								*
 *		2014/02/18		use id for elements if name not specified		*
 *						updateRow function moved to <form> object		*
 *						in editEvent.php script							*
 *		2014/02/19		updateRow feedback function renamed to			*
 *						updateCitation to make its function clearer		*
 *		2013/09/13		parameter names in array args are lower case	*
 *						use global debug flag							*
 *		2015/02/10		support being opened in <iframe>				*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2016/06/01		permit runninng in a half window				*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2015/06/16		open list of pictures in other half of window	*
 *		2016/02/06		call pageInit on load							*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/02/17      hide right column                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	                = loadEdit;

/************************************************************************
 *  childFrameClass														*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	            = 'right';

/************************************************************************
 *  function loadEdit													*
 *																		*
 *  This function is called when the page has been loaded.				*
 *  Initialize elements.												*
 *																		*
 *	Input:																*
 *		this			window											*
 ************************************************************************/
function loadEdit()
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

    document.body.onkeydown		= ecKeyDown;
    for (var fi = 0; fi < document.forms.length; fi++)
    {			        // loop through all forms in the document
		var	form		= document.forms[fi];

		if (form.name == 'citForm')
		{		// main form
		    // set action methods for elements
		    form.onsubmit		 	= validateForm;
		    form.onreset 			= resetForm;
		}		// main form

		// activate handling of key strokes in text input fields
		// including support for context specific help
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{		        // loop through all elements in the form
		    var element		= formElts[i];
		    if (element.nodeName.toLowerCase() == 'fieldset')
				continue;

		    // set behavior for individual elements by name
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    switch(name)
		    {	        // take action on specific elements
				case 'IDSR':
				{		// source selection list
				    element.addEventListener('click', sourceClick);
				    break;
				}		// source selection list

				case 'update':
				{		// <button id='update'>
				    element.addEventListener('click', updateCitation);
				    break;
				}		// update button

				case 'Pictures':
				{		// <button id='Pictures'>
				    element.addEventListener('click', editPictures);
				    break;
				}		// <button id='Pictures'>

				default:
				{
				    element.onkeydown	= keyDown;
				    element.onchange	= change;	// default handler
				    break;
				}

		    }	        // take action on specific elements
		}		        // loop through all elements in the form
    }			        // loop through all forms in the document

    hideRightColumn();
}		// function loadEdit

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
 *  function sourceClick												*
 *																		*
 *  This method is called when the user clicks on a selection			*
 *  list of sources. 													*
 *																		*
 *  Parameters:															*
 *      this    <select id="IDSR">                                      *
 *		index	the numeric index of the source selection list.			*
 *				The name field of this selection element is				*
 *				'Source' + index										*
 ************************************************************************/

function sourceClick(index)
{
    var	citForm		= document.citForm;
    var	elt		    = citForm.elements['IDSR'];
    if (elt.length > 1)
    {		// select element has already been populated
		return;
    }		// select element has already been populated

    // get the subdistrict information file
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=IDSR',
				gotSources,
				noSources);
}	// function sourceClick

/************************************************************************
 *  function gotSources													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the list of sources from the database is retrieved.					*
 ************************************************************************/
function gotSources(xmlDoc)
{
    var	citForm	    = document.citForm;

    // get the name of the associated select element
    var nameElts	= xmlDoc.getElementsByTagName('name');
    var	name		= '';
    if (nameElts.length >= 1)
    {		// name returned
		name	= nameElts[0].textContent;
    }		// name returned
    else
    {		// name not returned
		alert("editCitation.js: gotSources: name value not returned from getSourcesXml.php");
		return;
    }		// name not returned

    // get the list of sources from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("source");

    // locate the selection item to be updated
    var	elt		= citForm.elements['IDSR'];
    var	oldValue	= elt.options[elt.selectedIndex].value;
    elt.options.length	= 0;	// purge old options

    // add the options to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {		// loop through source nodes
		var	node	= newOptions[i];

		// get the text value to display to the user
		var	text	= node.textContent;

		// get the "value" attribute
		var	value	= node.getAttribute("id");
		if ((value == null) || (value.length == 0))
		{		// cover our ass
		    value		= text;
		}		// cover our ass

		// create a new HTML Option object and add it to the Select
		option	= addOption(elt,
					    text,
					    value);
		if (value == oldValue)
		    elt.selectedIndex	= i;	
    }		// loop through source nodes

}		// function gotSources

/************************************************************************
 *  function noSources													*
 *																		*
 *  This method is called if there is no sources file.					*
 ************************************************************************/
function noSources()
{
    alert("editCitation: noSources error");
}		// function noSources

/************************************************************************
 *  function updateCitation												*
 *																		*
 *  This method is called when the user requests that the				*
 *  citation be updated.  This is passed using AJAX to the				*
 *  updateCitation.php script.											*
 *																		*
 *  Input:																*
 *		this		<button id='update'>								*
 *		ev          onclick Event                                       *
 ************************************************************************/

function updateCitation(ev)
{
    if (!ev)
    {		// browser is not W3C compliant
		ev	=  window.event;	// IE
    }		// browser is not W3C compliant
    ev.stopPropagation();

    var	citForm		= document.citForm;
    var	opener	    = null;
    if (window.frameElement && window.frameElement.opener)
		opener	    = window.frameElement.opener;
    else
		opener	    = window.opener;
    if (opener)
    {		// notify opener of change
		var	formId		= 'citTable';
		// process the parameters
		var	msg	= '';
		for (var key in args)
		{		// loop through args
		    msg	+= key + "='" + args[key] + "', ";
		    switch(key)
		    {		// act on specific named arguments
				case 'formid':
				{
				    formId		= args[key];
				    break;
				}	// formId
		    }		// act on specific named arguments
		}		// loop through args
		if (debug == 'y')
		    alert('editCitation.js: updateCitation: args=' + msg);

		var	parentForm	= opener.document.getElementById(formId);
		var	idsrSel		= citForm.IDSR;
		if (parentForm)
		{
		  if (parentForm.updateCitation)
		  {
		  try {
		    var	idsx		= citForm.idsx.value;
		    var	idsr		= idsrSel.value;
		    var sourceName	= idsrSel.options[idsrSel.selectedIndex].text;
		    var	page		= citForm.SrcDetail.value;
		    if (debug == 'y')
				alert("editCitation.js: updateCitation: " +
				      "calling invoking page updateCitation(" +
						"idsx=" + idsx +
						",idsr=" + idsr +
						",sourceName='" + sourceName + "'" +
						",page='" + page + "')");
		    parentForm.updateCitation(idsx,
						      idsr,
						      sourceName,
						      page);
		  } catch (e) {
		    alert("editCitation.js: updateCitation: " +
				"feedback function updateCitation failed on invoking page: " . 
				e.message());
		  }
		}
		else
		{
		    var text	= "";
		    for(var nam in parentForm)
		    {
				text	+= nam + "=" + parentForm[nam] + ","; 
		    }
		    alert("editCitation.js: updateCitation: " +
		      "feedback function updateCitation not defined on invoking page: "+
		      "parentForm=" + parentForm.id);
		}
		}
		else
		    alert("editCitation.js: updateCitation: " +
					"form with id='" + formId +
					"' not found in opener's page"); 
    }		// notify opener of change

    if (debug.toLowerCase() == 'y')
        citForm.submit();
    else
    {               // use AJAX
	    var	parms	= {};
	    for (i=0; i < citForm.elements.length; i++)
	    {			// loop through all elements in the form
			var	elt		= citForm.elements[i];
			// copy element name and value to parms object
			if (elt.nodeName.toUpperCase() == 'SELECT')
			{		// <select>
			    parms[elt.name]	= elt.options[elt.selectedIndex].value;
			}		// <select>
			else
			if (elt.nodeName.toUpperCase() == 'TEXTAREA' &&
			    activateMCE)
			{		// <textarea>
			    parms[elt.name]	= tinyMCE.get(elt.name).getContent();
			}		// <textarea>
			else
			    parms[elt.name]	= elt.value;
	    }			// loop through all elements in the form

	    // update the citation in the database
	    HTTP.post('/FamilyTree/updateCitation.php',
			      parms,
			      gotCitation,
			      noCitation);
    }               // use AJAX
}	// function updateCitation

/************************************************************************
 *  function gotCitation												*
 *																		*
 *  This method is called when the XML file representing				*
 *  the updated Citation is returned from the server.                   *
 ************************************************************************/
function gotCitation(xmlDoc)
{
    var	citForm	= document.citForm;
    closeFrame();
}		// function gotCitation

/************************************************************************
 *  function noCitation													*
 *																		*
 *  This method is called if the server is unable to return the         *
 *  XML file representing the updated Citation.                         *
 ************************************************************************/
function noCitation()
{
    alert("editCitation: noCitation error");
}		// function noCitation

/************************************************************************
 *  function editPictures												*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures associated with the citation			*
 *  that are recorded by instances of Picture.							*
 *																		*
 *  Parameters:															*
 *		this		a <button> element									*
 *		ev          onclick Event                                       *
 ************************************************************************/
function editPictures(ev)
{
    if (!ev)
    {		// browser is not W3C compliant
		ev	=  window.event;	// IE
    }		// browser is not W3C compliant
    ev.stopPropagation();

    var	form		= this.form;
    var	picIdType	= form.PicIdType.value;
    var	idsx;

    if (form.idsx && form.idsx.value > 0)
    {		// idsx present in form
		idsx		= form.idsx.value;
		// open the list of pictures in the other half of the window
		openFrame("pictures",
				  "editPictures.php?idsx=" + idsx +
						  "&idtype=" + picIdType, 
				  childFrameClass);
    }		// idsx present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// function editPictures

/************************************************************************
 *  function ecKeyDown													*
 *																		*
 *  The key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Citation button.												*
 *																		*
 *  Parameters:															*
 *      this    <input type="text">                                     *
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function ecKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.key;

    // take action based upon code
    switch (code)
    {
		case 's':
		{		// letter 'S'
		    if (e.ctrlKey)
		    {		// ctrl-S
				var form	= document.forms[0];
				updateCitation();
				return false;
		    }		// ctrl-S
		    break;
		}		// letter 'S'

		case 'u':
		{		// letter 'U'
		    if (e.altKey)
		    {		// alt-U
				var form	= document.forms[0];
				updateCitation();
		    }		// alt-U
		    break;
		}		// letter 'U'

    }	    // switch on key code

    return;
}		// function ecKeyDown

