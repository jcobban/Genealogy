/************************************************************************
 *  OcfaQuery.js														*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  OcfaQuery.html														*
 *																		*
 *  History:															*
 *		2011/03/20		created											*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2012/05/07		templatize substitutions to support I18N		*
 *						support mouseover help							*
 *		2013/05/23		popup loading while waiting for township info	*
 *		2013/08/04		add facebook status								*
 *		2013/11/27		loading indicator for counties was				*
 *						mispositioned									*
 *		2014/01/01		remove <table>s and use CSS						*
 *		2018/01/24		remove getRightTop								*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad												*
 *																		*
 *  Obtain the list of counties in the province as an XML document.		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		if (form.name == "distForm")
		{
		    form.onsubmit 	= validateForm;
		    form.onreset 	= resetForm;
		}

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    // set up dynamic functionality based on the name of the element
		    switch(name)
		    {
				case 'County':
				{
				    popupLoading(element);
				    break;
				}

				case 'Surname':
				{
				    element.focus();
				    break;
				}

				case 'Status':
				{
				    element.onclick	= showStatus;
				    break;
				}

		    }	// switch on field name
		}		// loop through all elements in the form
    }		// loop through forms in the page

    // get the counties information file
    HTTP.getXML("/Ontario/OcfaCountiesXml.php",
				gotCountiesFile,
				noCountiesFile);
}		// onLoad

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
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one.					*
 ************************************************************************/
function resetForm()
{
    var	countySelect	= document.distForm.County;
    countySelect.onchange();	// repopulate Township selection
    return true;
}	// resetForm

/************************************************************************
 *  function gotCountiesFile											*
 *																		*
 *  This method is called when the counties file						*
 *  is retrieved.  It populates the selection statement.				*
 ************************************************************************/
function gotCountiesFile(xmlDoc)
{
    var	countySelect	        = document.distForm.County;
    // specify the action for selecting a county
    countySelect.onchange	    = changeCounty;

    var	countyText	            = document.distForm.CountyText;
    var selectedCounty          = countyText.value;
    countySelect.options.length	= 0;	// clear the selection
    countySelect.selectedIndex	= 0;
    var newOptions	            = xmlDoc.getElementsByTagName("option");

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    // add options corresponding the the elements in the XML document
    for (var i = 0; i < newOptions.length; ++i)
    {
		// get the source "option" node
		// note that although this has the same contents and appearance as an
		// HTML "option" statement, it is an XML Element object, not an HTML
		// Option object.
		var	node	= newOptions[i];

		// get the text value to display to the user
		var	text	= node.textContent;

		// get the "value" attribute
		var	value	= node.getAttribute("value");

		// create a new HTML Option object and add it to the Select
		var	newOption	= addOption(countySelect,
									text,
									value);
        if (text.length > 0 && text == selectedCounty)
        {
            newOption.selected      = true;
        }
    }			// loop through source "option" nodes
    if (countySelect.selectedIndex > 0)
        countySelect.onchange();

}		// gotCountiesFile

/************************************************************************
 *  function noCountiesFile												*
 *																		*
 *  This method is called if there is no census summary file.			*
 *  The selection list of counties is cleared and an error message		*
 *  displayed.															*
 ************************************************************************/
function noCountiesFile()
{
    // hide the loading indicator
    hideLoading();

    // clear anything in the county selection list
    var	countySelect	= document.distForm.County;
    countySelect.options.length	= 0;	// clear the selection

    // clear any existing message
    var	msgCell		= document.getElementById("msgCell");
    while (msgCell.hasChildNodes())
		msgCell.removeChild(msgCell.firstChild);

    // create the replacement contents
    // this is essentially cloneNode(true) with symbol substitutions
    var	newCell		= createFromTemplate("noCountyMsg",
									     {"province" : "ON"},
									     null);

    // insert the replacement contents
    while (newCell.hasChildNodes())
		msgCell.appendChild(newCell.removeChild(newCell.firstChild));
}		// noCountiesFile

/************************************************************************
 *  function changeCounty												*
 *																		*
 *  This method is called when the user selects a new county.			*
 *																		*
 *  Input:																*
 *		this	<select name='County'>									*
 ************************************************************************/
function changeCounty()
{
    // identify the selected county
    var	countySelect	    = this;
    var	form		        = countySelect.form;
    var	optIndex	        = countySelect.selectedIndex;
    //alert("changeCounty() optIndex=" + optIndex);
    if (optIndex == -1)		// no entry selected
		noTownship();
    else
    {
		var	optVal	= countySelect.options[optIndex].value;
        if (optVal.length > 0)
        {
		    // get the township information file
		    popupLoading(document.getElementById("TwpCell"));
		    HTTP.getXML('/Ontario/OcfaTownshipsXml.php?County=' +
			    		encodeURIComponent(optVal),
				    	gotTownship,
					    noTownship);
        }
    }
}		// changeCounty

/************************************************************************
 *  function gotTownship												*
 *																		*
 *  This method is called when the township information XML document	*
 *  relating to a particular county is retrieved.						*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document retrieved from server					*
 ************************************************************************/
function gotTownship(xmlDoc)
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    // clear out existing input prompt for township
    var	township	            = document.getElementById("Township");
    var	townshipText	        = document.getElementById("TwpText");
    var selectedTownship        = townshipText.value;
    var	container	            = township.parentNode;
    var	select		            = null;
    var nodeName	            = township.nodeName.toLowerCase();
    if (nodeName == 'select')
    {		// <select>
		select			        = township;
		select.options.length	= 0;	// clear out current selection
    }		// <select>
    else
    if (nodeName == 'input')
    {		// <input>
		nextChild	            = township.nextSibling;
		container.removeChild(township);
		select		            = document.createElement('select');
		select.setAttribute('name',     'Township');
		select.setAttribute('id',       'Township');
		select.setAttribute('class',    'actleft');
		select.setAttribute('size',     0);
		container.insertBefore(select, nextChild);
    }		// <input>

    // should have either pre-existing or created <select>
    if (select &&
		select.nodeType == 1 &&
		select.nodeName.toLowerCase() == 'select')
    {			// add options from XML document
		// create a new HTML Option object and add it 
		addOption(select,
				  'Choose a township',
				  '');
		// insert the option entries for the towns, cities and townships
		// in this county from the XML document
		var newOptions	= xmlDoc.getElementsByTagName("option");

		for (var i = 0; i < newOptions.length; ++i)
		{		// loop through source "option" elements
		    // get the source "option" element
		    // note that although this has the same contents as an
		    // HTML "option" statement, it is an XML Element object,
		    // not an HTML Option object.
		    var	xmlOptionElt	= newOptions[i];
    
		    // get the text value to display to the user
		    var	text	        = xmlOptionElt.textContent;
    
		    // get the "value" attribute
		    var	value	        = xmlOptionElt.getAttribute("value");
		    if ((value == null) || (value.length == 0))
		    {		// cover our ass
				value		    = text;
		    }		// cover our ass
    
		    // create a new HTML Option object and add it 
		    var newOption       = addOption(select,
                        					text,
					                        value);
            if (text.length > 0 && text == selectedTownship)
                newOption.selected      = true;
		}		// loop through source "option" elements
    }			// add options from XML document
}		// gotTownship

/************************************************************************
 *  function noTownship													*
 *																		*
 *  This method is called if there is no township						*
 *  description file returned by the server.							*
 *  The selection list of townships is replaced by a text input field.	*
 ************************************************************************/
function noTownship()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    // clear out existing input prompt for township
    var	twpCell		= document.getElementById("TwpCell");
    var	input		= null;
    for(var child = twpCell.firstChild; child; child = child.nextSibling)
    {			// loop through children
		if (child.nodeType == 1)
		{		// element
		    var nodeName	= child.nodeName.toLowerCase();
		    if (nodeName == 'select')
		    {		// <select>
				var	nextChild	= child.nextSibling;
				twpCell.removeChild(child);
				input	= document.createElement('input');
				input.setAttribute('name', 'Township');
				input.setAttribute('id', 'Township');
				input.setAttribute('class', 'actleft');
				input.setAttribute('size', 20);
				twpCell.insertBefore(input, nextChild);
				child	= input;
		    }		// <select>
		    else
		    if (nodeName == 'input')
		    {		// <input>
				input		= child;
				input.value	= '';
		    }		// <input>
		}		// element
    }			// loop through children
}		// noTownship

/************************************************************************
 *  function showStatus													*
 *																		*
 *  Switch to the transcription status page.							*
 *  This function is called when the ShowStatus button is selected.		*
 ************************************************************************/
function showStatus()
{
    location	= "OcfaStats.php";
    return false;	// suppress default action
}		// showStatus

