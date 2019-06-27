/************************************************************************
 *  locationCommon.js													*
 *																		*
 *  Javascript code to implement common functionality of scripts		*
 *  in the FamilyTree database management system.						*
 *  This set of routines is shared between the following scripts:		*
 *  	FamilyTree/editEvent						                    * 
 *  	FamilyTree/editIndivid											*
 *  	FamilyTree/editMarriages 										*
 *  	FamilyTree/editParents 											*
 *  	FamilyTree/Locations											*
 *  	Canada/BirthRegDetail											*
 *  	Ontario/DeathRegDetail											*
 *  	Ontario/MarriageRegDetail										*
 *																		*
 *  History:															*
 *		2011/10/01		created											*
 *		2011/10/28		correct error messages that still reference		*
 *						editIndivid.js									*
 *		2012/01/13		change class names								*
 *						add "residence" to list of words not capitalized*
 *		2012/07/04		suppress location lookup for blank				*
 *		2012/08/25		support EventLocation fields in locationChanged	*
 *		2013/01/08		add abbreviations for prepositions in locations	*
 *		2013/03/11		rename changeLocation as locationChanged		*
 *		2013/05/26		replace use of alert for displaying message		*
 *						about new location								*
 *		2014/01/01		use the innerHTML property for getting the text	*
 *			            value of an <option> tag to support old		    * 
 *						releases of IE									*
 *		2014/02/21		handling of EventLocation changed due to 		*
 *						migration to use of CSS for layout.				*
 *						renamed to locationCommon.js					*
 *		2014/10/12		use method show to display dialog				*
 *		2014/12/08		locationChanged method now handles any element	*
 *						whose name contains the text 'Location'			*
 *		2015/07/05		extra semicolon in text							*
 *		2015/08/14		set focus on <select> in selection dialog		*
 *						add workaround for bug in FF 40 and Chromium	*
 *		2017/07/30		support afterChange handler for location		*
 *		2018/01/08		put focus on updated location field after		*
 *						update.											*
 *		2018/01/09		add fractions 1/3 and 2/3						*
 *						correct placement of focus after user accepts	*
 *						notification of previously undefined location	*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2018/12/28      dynamically load templates                      *
 *		2019/01/22      do not lookup locations [blank] or [N/A]        *
 *		2019/03/03      myform was no longer defined                    *
 *		2019/05/28      do not treat special characters in location     *
 *		                as regular expression operators                 *
 *		2019/06/12      correctly set focus to next element when a      *
 *		                new location is defined                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  evtLocAbbrs															*
 *																		*
 *  Table for expanding abbreviations for locations						*
 ************************************************************************/
var	evtLocAbbrs = {
				"1/4" :			"¼",
				"1/3" :			"&#8531;",
				"1/2" :			"½",
				"2/3" :			"&#8532;",
				"3/4" :			"¾",
				"1rn" :			"1RN",
				"2rn" :			"2RN",
				"3rn" :			"3RN",
				"4rn" :			"4RN",
				"5rn" :			"5RN",
				"1rs" :			"1RS",
				"2rs" :			"2RS",
				"3rs" :			"3RS",
				"4rs" :			"4RS",
				"5rs" :			"5RS",
				"Ab" :			"AB",
				"At" :			"at",
				"Bc" :			"BC",
				"By" :			"by",
				"Ca" :			"CA",
				"Con" :			"con",		// suppress capitalization
				"Elg" :			"Elgin",
				"Esx" :			"Essex",
				"For" :			"for",		// suppress capitalization
				"From" :		"from",		// suppress capitalization
				"In" :			"in",		// suppress capitalization
				"Lmt" :			"Lambton", 
				"Lot" :			"lot",		// suppress capitalization
				"Mb" :			"MB",
				"Msx" :			"Middlesex",
				"Nb" :			"NB",
				"Ne" :			"NE",
				"Nl" :			"NL",
				"Ns" :			"NS",
				"Nw" :			"NW",
				"Of" :			"of",		// suppress capitalization
				"On" :			"ON",
				"Or" :			"or",		// suppress capitalization
				"P.o." :		"P.O.",
				"Pi" :			"PI",
				"Qc" :			"QC",
				"Res" :			"residence",
				"Res." :		"residence",
				"Residence" :	"residence",	// suppress capitalization
				"Se" :			"SE",
				"Sk" :			"SK",
				"Sw" :			"SW",
				"Us" :			"USA",
				"Usa" :			"USA",
				"[" :			"[blank]"
				};

// get an XML file containing dialog templates from the server
var lang                = 'en';
var args                = getArgs();
if ('lang' in args)
    lang                = args.lang;
var url	= "/LocationDialogsXML.php?lang=" + lang;

HTTP.getXML(url,
		    gotDialogs,
		    noDialogs);

/************************************************************************
 *  function gotDialogs													*
 *																		*
 *  This method is called when the HTML document representing			*
 *  the dialog templates is received from the server.                   *
 *																		*
 *  Input:																*
 *		xmlDoc          XHTML document                                  *
 ************************************************************************/
var topXml          = null;
var forms           = [];

function gotDialogs(xmlDoc)
{
    topXml	    = xmlDoc.documentElement;
    if (topXml && topXml.nodeName == 'div')
    {			// valid response
        for(var elt = topXml.firstChild; elt !== null; elt = elt.nextSibling)
        {           // loop through children
            if (elt.nodeType == 1)
            {       // element
                var attrs       = elt.attributes;
                for (var ia = 0; ia < attrs.length; ia++)
                {
                    var name                    = attrs[ia].name;
                    var value                   = attrs[ia].value;
                    if (name == 'id')
                    {
                        elt.id                  = value;
                        forms[value]            = elt;
                    }
                    else
                    if (name == 'name')
                        elt.name                = value;
                }
                elt.elements                    = [];
                traverse(elt, elt);
            }       // element
        }           // loop through children
    }			// valid response
    else
        alert("locationCommon.js: gotDialogs: " + tagToString(xmlDoc));
}       // function gotDialogs

/************************************************************************
 *  function traverse    														*
 *																		*
 *  Recursively traverse all of the children of an XML node.            *`
 *																		*
 *  Input:																*
 *		elt         instance of Node                                    *
 *		form        instance of Node at the top of the tree             *
 ************************************************************************/
function traverse(elt, form)
{
    for(var child = elt.firstChild; child !== null; child = child.nextSibling)
    {           // loop through children
        if (child.nodeType == 1)
        {       // element
            if (child.nodeName == 'input' || 
                child.nodeName == 'button' ||
                child.nodeName == 'select' ||
                child.nodeName == 'textarea')
            {   // form element
                child.form      = form.id;
                var attrs       = child.attributes;
                for (var ia = 0; ia < attrs.length; ia++)
                {
                    if (attrs[ia].name == 'name')
                    {
                        child.name              = value;
                        form.elements[child.name]       = child;
                    }
                }
                traverse(child, form);
            }   // form element
        }       // element
    }           // loop through children
}       // function traverse

/************************************************************************
 *  function noDialogs 													*
 *																		*
 *  This method is called if there is no response script on the server	*
 ************************************************************************/
function noDialogs()
{
    alert("locationCommon.js: cannot find LocationDialogs");
}       // function noDialogs

var myform          = null;
/************************************************************************
 *  function deferSubmit												*
 *																		*
 *  Common global flag to prevent submit from completing until all		*
 *  required operations to resolve a location are completed.			*
 ************************************************************************/
var	deferSubmit	= false;

/************************************************************************
 *  function locationChanged											*
 *																		*
 *  Take action when the user changes a field containing a location		*
 *  name to implement assists such as converting to upper case,			*
 *  expanding abbreviations, and completing short form names.			*
 *  This is the onchange method of any input text field that contains	*
 *  location text that is to be mapped to a reference to a				*
 *  LegacyLocation.														*
 *																		*
 *  Input:																*
 *		this				an instance of <input type='text'>			*
 ************************************************************************/
function locationChanged()
{
    var	form			        = this.form;
    var	updateButton		    = document.getElementById('updEvent');
    if (updateButton)
		updateButton.disabled	= true;


    // get the name of the input field
    var	name;
    var	ider	= 0;

    if (this.name)
		name	= this.name;
    else
    if (this.id)
		name	= this.id;
    else
		name	= '';

    // check for event location fields
    var	locOffset	= name.indexOf('Location');
    if (locOffset > 0)
    {			// special case for EventLocation input fields
		var	row		= this.parentNode;
		for(var ic = 0; ic < row.children.length; ic++)
		{
		    var child		= row.children[ic];
		    if (child.id && child.id.length > 12 &&
				child.id.substring(0,12) == 'EventChanged')
		    {		// found EventChanged
				// notify the script updateIndividXml.php that
				// this event has been changed
				child.value	= 1;
		    }		// found EventChanged
		}		// loop through all elements in row
    }			// special case for EventLocation input fields
 
    // trim off leading and trailing spaces
    this.value	= this.value.trim();

    // if the form has a button named Submit, enable it just in case
    // it was previously disabled
    var	submitButton	= document.getElementById('Submit');
    if (submitButton)
		submitButton.disabled	= false;

    // if the value is explicitly [blank] accept it
    if (this.value == '[' || this.value == '[blank]' || this.value == '[Blank]')
    {
		this.value	= '[Blank]';
		return;
    }

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

    // if possible display a loading indicator to the user so he/she is
    // aware that the location lookup is being performed
    var loc             = this.value.toLowerCase();
    if (loc.length != 0 && loc != '[blank]' && loc != '[n/a]')
    {		// search only for non-blank location
		popupLoading(this);

		// get an XML file containing location information from the database
        var loc         = this.value;
        loc             = loc.replace(/\?/g, '\\?');
        loc             = loc.replace(/\./g, '\\.');
        loc             = loc.replace(/\[/g, '\\[');
        loc             = loc.replace(/\*/g, '\\*');
        loc             = loc.replace(/\^/g, '\\^');
        loc             = loc.replace(/\$/g, '\\$');
		var url	= "/FamilyTree/getLocationXml.php?name=" +
						encodeURIComponent(loc) +
						"&form=" + this.form.name +
						"&field=" + this.name;
		HTTP.getXML(url,
				    gotLocationXml,
				    noLocationXml);
        deferSubmit			    = true;
    }		// search only for non-blank location
    else
        deferSubmit			    = false;
}		// function locationChanged

/************************************************************************
 *  function gotLocationXml												*
 *																		*
 *  This method is called when the XML document representing			*
 *  the location or locations is retrieved from the database.			*
 *																		*
 *  Input:																*
 *		XML document with response, for example							*
 *																		*
 *		<locations count="1" name="Caradoc" form="formname"				*
 *				field="fieldname">										*
 *		  <cmd>SELECT * FROM tblLR WHERE Location='Caradoc' ....</cmd>	*
 *		  <location idlr="17">											*
 *		    <idlr>17</idlr>												*
 *		    <fsplaceid/>												*
 *		    <preposition/>												*
 *		    <location>Caradoc, Middlesex, ON, CA</location>				*
 *		    <sortedlocation>Caradoc, Middlesex, ON, CA</sortedlocation>	*
 *		    <shortname>Caradoc</shortname>								*
 *		    ...															*
 *		  </location>													*
 *		</locations>													*
 ************************************************************************/
function gotLocationXml(xmlDoc)
{
    var	topXml	= xmlDoc.documentElement;
    if (topXml && typeof(topXml) == "object" && topXml.nodeName == 'locations')
    {			// valid response
		var count	    = 0;
		var field	    = '';		// initiating field name
		var formname	= '';		// form containing field
		var name	    = '';		// search argument
		var attrs	    = '';		// for generating alert message
		for(var i = 0; i < topXml.attributes.length; i++)
		{
		    var attr	        = topXml.attributes[i];
		    attrs	            += attr.name + "='" + 
					                attr.value + "', ";
		    switch(attr.name)
		    {
				case 'count':
				{	// number of matches
				    count	    = parseInt(attr.value);
				    break;
				}	// count

				case 'name':
				{	// search argument
				    name	    = attr.value;
				    break;
				}	// search argument

				case 'field':
				{	// name of field initiating search
				    field	    = attr.value;
				    break;
				}	// field

				case 'form':
				{	// form name containing field
				    formname	= attr.value;
				    break;
				}	// form name
		    }		// act on specific attributes
		}		// loop through attributes

		// locate the form containing the element that initiated the request
		var	form	            = document.forms[formname];
		if (form === undefined)
		{		// form not found
		    alert("locationCommon.js: gotLocationXml: form name='" + formname +
					"' not found");
		    return;
		}		// form not found

		// locate the element that initiated the request
		var	element	            = form.elements[field];
		if (element === undefined)
		{		// element not found
		    alert("locationCommon.js: gotLocationXml: element name='" + field +
					"' not found in form");
		    return;
		}		// element not found

		// if there is exactly one location matching the request then
		// replace the text value of the element with the full location
		// name from the database
		if (count == 1)
		{		// exactly one matching entry
		    for(var j = 0; j < topXml.childNodes.length; j++)
		    {		// loop through children of top node
				var child	    = topXml.childNodes[j];
				if (child.nodeType == 1 && child.nodeName == 'location')
				{	// <location> element representing record
				    for(var k = 0; k < child.childNodes.length; k++)
				    {		// loop through children of <location> record
					// each elt represents a field in the database record
					// or possibly text for presentation purposes
					var elt	    = child.childNodes[k];
					if (elt.nodeType == 1 && elt.nodeName == 'location')
					{	// location field
					    element.value	= elt.textContent.trim();
					    break;	// stop searching
					}	// location field
				    }		// loop through children of <location> record
				    break;	// only look at first <location> record
				}	// <location> element representing record
		    }		// loop through children of top node

		    // location field is updated
		    deferSubmit			= false;
		    var	updateButton		= document.getElementById('updEvent');
		    if (updateButton)
				updateButton.disabled	= false;

		    // check for action to take after changed
		    if (element.afterChange)
				element.afterChange();
		    else
				focusNext(element);
		}		// exactly one matching location
		else
		if (count == 0)
		{		// no matching entries
		    var	msgDiv	= document.getElementById('msgDiv');
		    if (msgDiv)
		    {		// have popup <div> to display message in
				var parms   = {"template"	: "",
						        "name"	    : name,
						        "formname"	: formname,
						        "field"	    : field};
				try {
				displayDialog(msgDiv,
						      forms['NewLocationMsg$template'],
						      parms,
						      element,		// position
						      closeNewDialog,	// button closes dialog
						      false);		// default show on open
					} catch (e) { 
				    alert("locationCommon.js: gotLocationXml: display NewLocationMsg dialog failed: " +
					  e.message); 
				}
		    }		// have popup <div> to display message in
		    else
				alert("locationCommon.js: gotLocationXml: Note: '" + name +
					"' is a previously undefined location.");
		}		// no matching entries
		else
		{		// multiple matching entries
		    var	msgDiv	        = document.getElementById('msgDiv');
		    if (msgDiv)
		    {		// have popup <div> to display message in
				var parms	    = { "template"	: "",
						            "name"	    : name};
				try {
				displayDialog(msgDiv,
						      forms['ChooseLocationMsg$template'],
						      parms,
						      element,		// position
						      null,		    // button closes dialog
						      true);		// do not show yet

				// update selection list for choice
				var	select	    = document.getElementById('locationSelect');
				select.onchange	= locationChosen;
				select.setAttribute("for", field);
				select.setAttribute("formname", formname);
				    
				for(var j = 0; j < topXml.childNodes.length; j++)
				{		// loop through children of top node
				    var child	= topXml.childNodes[j];
				    if (child.nodeType == 1 && child.nodeName == 'location')
				    {	// <location> element representing record
						var	idlr	= 0;
						var	locname	= "";
	
						for(var k = 0; k < child.childNodes.length; k++)
						{	// loop through children of <location> record
						    // each elt represents a field in the database
						    // record or possibly text for presentation purposes
						    var elt	= child.childNodes[k];
						    if (elt.nodeType == 1)
						    {	// element (tag) node
								if (elt.nodeName == 'idlr')
								{	// numeric key field
								    idlr	= parseInt(elt.textContent.trim());
								}	// numeric key field
								else
								if (elt.nodeName == 'location')
								{	// location name field
								    locname	= elt.textContent.trim();
								}	// location name field
						    }	// element (tag) node
						}	// loop through children of <location> record
					    
						// create option element under select
						var	option	= new Option(locname,
									     idlr, 
									     false, 
									     false);
						// IE<8 does not create option element correctly
						option.innerHTML	= locname;
						option.value		= idlr;	
					    select.appendChild(option);
				    }	// <location> element representing record
				}	// loop through children of top node
				select.selectedIndex	= 0;

				// make the dialog visible
				show(msgDiv);
				// the following is a workaround for a bug in FF 40.0 and
				// Chromium in which the onchange method of the <select> is
				// not called when the mouse is clicked on an option
				for(var io=0; io < select.options.length; io++)
				{
				    var option	= select.options[io];
				    option.addEventListener("click", function() {this.selected = true; this.parentNode.onchange();});
				}
				select.focus();

				} catch (e) { 
				    alert("locationCommon.js: gotLocationXml: display ChooseLocationMsg dialog failed: " +
					  e.message); 
				}
		    }		// have popup <div> to display message in
		    else
				alert("locationCommon.js: gotLocationXml: cannot find <div id='msgDiv'> in which to display dialog");
		}		// multiple matching entries
    }			// valid response
    else
    {
		if (topXml && typeof(topXml) == "object")
		    alert("locationCommon.js: gotLocationXml: " + tagToString(topXml));
		else
		    alert("locationCommon.js: gotLocationXml: '" + xmlDoc + "'");
    }

    hideLoading();	// hide the "loading" indicator
}		// gotLocationXml

/************************************************************************
 *  function closeNewDialog												*
 *																		*
 *  This closes (hides) the new location dialog and reenables the		*
 *  update button.														*
 *																		*
 *  Input:																*
 *		this		the HTML <button> element							*
 ************************************************************************/
function closeNewDialog()
{
    // no longer displaying the modal dialog popup
    var	msgDiv	            = document.getElementById('msgDiv');
    msgDiv.style.display	= 'none';	// hide
    deferSubmit			    = false;
    var	updateButton		= document.getElementById('updEvent');
    if (updateButton)
		updateButton.disabled	= false;

    var myform              = this.form;
    if (myform)
    {                           // the dialog includes a form
        var nameelt         = null;
        var fieldelt        = null;
        var elements        = myform.elements;
        for(var ie = 0; ie < elements.length; ie++)
        {
            var element     = elements[ie];
            switch(element.name)
            {
                case 'formname':
                    nameelt     = element;
                    break;

                case 'field':
                    fieldelt    = element
                    break;

            }
        }
        var formname        = '';
        if (typeof(nameelt) == 'object')
	        formname	   	= nameelt.value;
        else
            alert("locationCommon.js: closeNewDialog: missing element formname: " . myform.outerHTML);
	    var	field		   	= '';
        if (typeof(fieldelt) == 'object')
        {
	        field		   	= fieldelt.value;
		    var	mainForm	= document.forms[formname];
		    var	element		= mainForm.elements[field];
		    if (element)
		    {                       // found requested field in invoking form
				focusNext(element);
		    }                       // found requested field in invoking form
		    else
		    {                       // issue diagnostic
				var	elementList	= '';
				var	comma		= '[';
				for(var fieldname in mainForm.elements)
				{
				    elementList	+= comma + fieldname;
				    comma		= ',';
				}
				alert("locationCommon.js: closeNewDialog: cannot find input element with name='" + field + "' in form '" + formname + "' elements=" + elementList + "]");
		    }                       // issue diagnostic
        }
        else
            alert("locationCommon.js: closeNewDialog: missing element field: " . myform.outerHTML);
    }                           // the dialog includes a form
    else
		alert("locationCommon.js: closeNewDialog: cannot find <form> in open dialog");
    return null;
}		// closeNewDialog

/************************************************************************
 *  function noLocationXml												*
 *																		*
 *  This method is called if there is no response script on the server	*
 ************************************************************************/
function noLocationXml()
{
    alert("locationCommon.js: logic error: " +
				"getLocationXml.php script not found on server");
}		// noLocationXml

/************************************************************************
 *  function locationChosen												*
 *																		*
 *  This method is called when the user chooses a location from			*
 *  the dynamic selection list.											*
 *																		*
 *  Input:																*
 *		this				<select> element							*
 ************************************************************************/
function locationChosen()
{
    var	chosenOption	= this.options[this.selectedIndex];

    if (chosenOption.value > 0)
    {		// ordinary entry
		var	form		= document.forms[this.getAttribute("formname")];
		var	elementName	= this.getAttribute("for");
		var	element		= form.elements[elementName];
		if (element)
		{
		    element.value	= chosenOption.innerHTML;

		    // check for action to take after changed
		    if (element.afterChange)
				element.afterChange();
		    else
				focusNext(element);
		}
		else
		    alert("locationCommon.js: locationChosen: cannot find input element with name='" + elementName + "'");
    }		// ordinary entry

    closeNewDialog.call(this);
}		// locationChosen

/************************************************************************
 *  function focusNext													*
 *																		*
 *  This function sets the focus on the next input element after		*
 *  the supplied element.												*
 *																		*
 *  Input:																*
 *		element			instance of HtmlElement							*
 ************************************************************************/
function focusNext(element)
{
    var form		= element.form;
    var	elements	= form.elements;
    var	searching	= true;
    var	trace		= '';
    for (var ie = 0; ie < elements.length; ie++)
    {				// loop through form elements
		var e		= elements[ie];
		if (searching)
		    trace	+= ", searching " + e.outerHTML;
		else
		    trace	+= ", getNext " + e.outerHTML;
		if (e === element)
		    searching	= false;
		else
		if (!searching)
		{			// get next active element
		    if (!e.disabled)
		    {
				e.focus();
				break;
		    }
		}			// get next active element
    }				// loop through form elements
}		// function focusNext
