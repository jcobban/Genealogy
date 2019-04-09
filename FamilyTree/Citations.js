/************************************************************************
 *  Citations.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Citations.php, which implements the ability to identify		*
 *  citations that match a pattern.										*
 *																		*
 *  History:															*
 *		2011/07/05		created											*
 *		2012/01/13		change class names								*
 *		2012/02/25		add help support for mouseover					*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/07/31		defer setup of facebook link					*
 *		2014/07/04		type selection list set up entirely by PHP now	*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
window.onload	= loadPage;

/************************************************************************
 *  function loadPage													*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function loadPage()
{
    var	type		= 0;
    var	idsr		= 0;

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];

		if (form.name == 'citForm')
		{	// set action methods for form
		    form.onsubmit		 	= validateForm;
		    form.onreset 			= resetForm;
		}	// set action methods for form

		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    // take action specific to element
		    var	name;
		    if (element.name && element.name.length > 0)
				    name	= element.name;
		    else
				    name	= element.id;

		    switch(name)
		    {		// act on field name
				case 'typeparm':
				{
				    type	= parseInt(element.value);
				    break;
				}	// typeparm

				case 'idsrparm':
				{
				    idsr	= parseInt(element.value);
				    break;
				}	// idsrparm

				case 'type':
				{
				    break;
				}	// type

		    }		// act on field name
		}		// loop through all elements
    }			// loop through all forms
    
    // get the list of defined sources to populate the select
    // for IDSR value.  The name of the <select> element,
    // the numeric key of the <option> to select, and the name of
    // the <form> are passed as parameters so they can be returned
    // in the response.
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=idsr' +
						'&idsr=' + idsr +
						'&formname=citForm',
				gotSources,
				noSources);
}		// loadPage

/************************************************************************
 *  validateForm														*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  resetForm																*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  gotSources																*
 *																		*
 *  This method is called when the XML file representing				*
 *  the list of sources from the database is retrieved.						*
 *																		*
 *  Parameters:																*
 *		xmlDoc		information about the defined sources as an XML document*
 ************************************************************************/
function gotSources(xmlDoc)
{
    // get the name of the select element to be updated from the XML document
    var nameElts	= xmlDoc.getElementsByTagName('name');
    var	name		= '';
    if (nameElts.length >= 1)
    {		// name returned
		name	= nameElts[0].textContent;
    }		// name returned
    else
    {		// name not returned
		alert("Citations.js: gotSources: name value not returned from getSourcesXml.php");
		return;
    }		// name not returned

    // get the idsr of the select option to be highlighted
    var idsrElts	= xmlDoc.getElementsByTagName('idsr');
    var	idsr		= null;
    if (idsrElts.length >= 1)
    {		// idsr returned
		idsr	= idsrElts[0].textContent;
    }		// idsr returned

    // get the formname of the select option to be highlighted
    var formnameElts	= xmlDoc.getElementsByTagName('formname');
    var	formname	= null;
    if (formnameElts.length >= 1)
    {		// formname returned
		formname	= formnameElts[0].textContent;
    }		// formname returned
    else
    {		// name not returned
		alert("Citations.js: gotSources: formname value not returned from getSourcesXml.php");
		return;
    }		// name not returned

    // the form element in the web page
    var	form		= document.forms[formname];

    // get the list of sources from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("source");

    // locate the selection element in the web page to be updated
    var	elt		= form.elements[name];
    if (elt == null)
    {
		var msg	= "";
		for(var i=0; i < form.elements.length; i++)
		{
		    msg += form.elements[i].name + ", ";
		    if (form.elements[i].name == name)
		    {
				elt	= form.elements[i];
				break;
		    }
		}
		if (elt == null)
		{		// elt still null
		alert("Citations.js: gotSources: could not find named element " +
				name + ", element names=" + msg);
		return;
		}		// elt still null
    }

    // purge old options on the select if any
    if (elt.options)
		elt.options.length	= 0;	// purge old options if any
    else
		alert("Citations.js: gotSources:" + tagToString(elt));

    // create a new HTML Option object to represent the ability to
    // create a new source and add it to the Select as the first option
    elt.size	= 10;

    // add the options from the XML file to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {		// loop through source nodes
		var	node	= newOptions[i];

		// get the text value to display to the user
		// this is the name of the source
		var	text	= node.textContent;

		// get the "id" attribute, this is the IDSR value identifying
		// the source.  It becomes the value of the Option. 
		var	value	= node.getAttribute("id");
		if ((value == null) || (value.length == 0))
		{		// cover our ass
		    value		= text;
		}		// cover our ass

		// create a new HTML Option object and add it to the Select
		option	= addOption(elt,	// Select element
					    text,	// text value to display
					    value);	// unique key of source record

		// select the last source chosen by the user
		if (idsr &&
		    (value == idsr))
			option.selected	= true;

    }		// loop through source nodes

    elt.focus();		// give selection list the focus
		
}		// gotSources

/************************************************************************
 *  noSources																*
 *																		*
 *  This method is called if there is no sources						*
 *  file.																*
 ************************************************************************/
function noSources()
{
    alert("Citations.js: noSources error");
}		// noSources
