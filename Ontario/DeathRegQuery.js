/************************************************************************
 *  DeathRegQuery.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page DeathRegQuery.html.											*
 *																		*
 *  History:															*
 *		2011/06/17		set initial input focus on registration year	*
 *						field											*
 *						change name of onload handler					*
 *						clean up comments								*
 *		2011/11/06		support mousover help							*
 *						support button for displaying transcription		*
 *						status											*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2012/05/09		invoke scripts to obtain database information	*
 *						rather than loading static files				*
 *						show loading indicator							*
 *		2013/08/04		defer initialization of facebook link			*
 *		2014/01/03		use CSS for layout instead of tables			*
 *		2014/08/29		change name of row limit to Limit				*
 *		2017/12/30		TownshipsListXml.php moved to folder Canada		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoadPage;

/************************************************************************
 *  onLoadPage																*
 *																		*
 *  Obtain the list of counties in the province as an XML file.				*
 ************************************************************************/
function onLoadPage()
{
    pageInit();

    var	form	= document.distForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		form.onsubmit 	= validateForm;
		form.onreset 	= resetForm;

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {		// set mouseover on containing cell
			element.parentNode.onmouseover	= eltMouseOver;
			element.parentNode.onmouseout	= eltMouseOut;
		    }		// set mouseover on containing cell
		    else
		    {		// set mouseover on input element itself
			element.onmouseover		= eltMouseOver;
			element.onmouseout		= eltMouseOut;
		    }		// set mouseover on input element itself

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
			case 'RegYear':
			{	// year of registration
			    // put the initial focus in the registration year field
			    element.focus();
			    break;
			}	// year of registration

			case "ShowStatus":
			{
			    element.onclick	= showStatus;
			    break;
			}

			case "RegCounty":
			{
			    popupLoading(element);
			    // get the counties information file
			    HTTP.getXML("/Canada/CountiesListXml.php?Prov=ON",
					gotCountiesFile,
					noCountiesFile);
			    break;
			}

		    }	// switch on field name
		}	// loop through all elements in the form
    }		// loop through forms in the page
}		// onLoadPage

/************************************************************************
 *  validate Form														*
 *																		*
 *  Ensure that the data entered by the user has been minimally				*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this		<form>														*
 ************************************************************************/
function validateForm()
{
    var	form	= this;
    var yearPat	= /^\d{4}$/;
    var numPat	= /^\d{1,6}$/;
    var countPat= /^\d{1,2}$/;

    var	msg	= "";
    if ((form.RegYear.value.length > 0) && 
		form.RegYear.value.search(yearPat) == -1)
		msg	= "Year is not 4 digit number. ";
    if ((form.RegNum.value.length > 0) &&
		form.RegNum.value.search(numPat) == -1)
		msg	+= "Number is not a valid number. ";
    if ((form.Limit.value.length > 0) &&
		form.Limit.value.search(countPat) == -1)
		msg	+= "Limit is not a 1 or 2 digit number. ";

    if (msg != "")
    {
		alert(msg);
		return false;
    }
    return true;
}		// validateForm

/************************************************************************
 *  resetForm																*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one.						*
 ************************************************************************/
function resetForm()
{
    changeCounty();	// repopulate Township selection
    return true;
}	// resetForm

/************************************************************************
 *  gotCountiesFile														*
 *																		*
 *  This method is called when the counties file						*
 *  is retrieved.  It populates the selection statement.				*
 *																		*
 *  Parameters:																*
 *		xmlDoc		the counties table as an XML document						*
 ************************************************************************/
function gotCountiesFile(xmlDoc)
{
    var	countySelect	= document.distForm.RegCounty;
    countySelect.options.length	= 0;	// clear the selection
    var newOptions		= xmlDoc.getElementsByTagName("option");

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    addOption(countySelect,
		      "Choose a county:",
		      "");
    // add options corresponding the the elements in the XML file
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
    }			// loop through source "option" nodes

    // specify the action for selecting a county
    countySelect.onchange	= changeCounty;
    countySelect.onchange();	// invoke it for the first time
    countySelect.selectedIndex	= 0;
}		// gotCountiesFile

/************************************************************************
 *  noCountiesFile														*
 *																		*
 *  This method is called if there is no file of county names.				*
 *  The selection list of countys is cleared and an error message		*
 *  displayed.																*
 ************************************************************************/
function noCountiesFile()
{
    // hide the loading indicator
    hideLoading();

    // clear anything in the county selection list
    var	countySelect	= document.distForm.RegCounty;
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

    // empty anything left in township input field
    var	element		= document.getElementById("RegTownship");
    if (element.options)
		element.options.length	= 0;
    else
		element.value	= '';
}		// noCountiesFile

/************************************************************************
 *  changeCounty														*
 *																		*
 *  This method is called when the user selects a new county.				*
 *																		*
 *  Input:																*
 *		this		<select name='RegCounty'>								*
 ************************************************************************/
function changeCounty()
{
    // identify the selected county
    var	countySelect	= document.distForm.RegCounty;
    var	optIndex	= countySelect.selectedIndex;
//alert("changeCounty() optIndex=" + optIndex);
    if (optIndex == -1)		// no entry selected
		noTownship();
    else
    {
		var	optVal	= countySelect.options[optIndex].value;
		if (optVal.length > 0)
		{	// have a county code
		    // identify the file containing township information for
		    // the selected county
		    var subFileName	= "/Canada/TownshipsListXml.php?Prov=ON&County=" +
						optVal;
		    if (debug != 'n')
			alert('DeathRegQuery.js: changeCounty: ' + subFileName);
		
		    // get the township information file
		    HTTP.getXML(subFileName,
				gotTownship,
				noTownship);

		    popupLoading(document.distForm.RegTownship);
		}	// have a county code
    }

}		// changeCounty

/************************************************************************
 *  showStatus																*
 *																		*
 *  Switch to the transcription status page.								*
 *  This function is called when the ShowStatus button is selected.		*
 *																		*
 *  Input:																*
 *		this				<button id='Stats'>								*
 ************************************************************************/
function showStatus()
{
    location	= "DeathRegStats.php";
    return false;	// do not submit form
}		// showStatus
