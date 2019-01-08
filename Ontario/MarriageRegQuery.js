/************************************************************************
 *  MarriageRegQuery.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  MarriageRegQuery.html												*
 *																		*
 *  History:															*
 *		2011/03/14		created											*
 *		2011/10/28		support mouseover help							*
 *						replace link to status with button				*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2012/05/09		invoke scripts to obtain database information	*
 *						rather than loading static files				*
 *						show loading indicator							*
 *		2013/08/04		defer initialization of facebook link			*
 *		2014/01/03		use CSS for layout instead of tables			*
 *		2014/02/10		support multiple domains through selection		*
 *		2016/05/20		counties list script moved to folder Canada		*
 *		2017/12/30		TownshipsListXml.php moved to folder Canada		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban.								*
 ************************************************************************/

window.onload	= loadCounties;

/************************************************************************
 *  function loadCounties												*
 *																		*
 *  Obtain the list of counties in the province as an XML file.			*
 *																		*
 *  Input:																*
 *		this		Window object										*
 ************************************************************************/
function loadCounties()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		if (form.name == 'distForm')
		{
		    form.onsubmit	= validateForm;
		    form.onreset	= resetForm;
		}

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    // do not process fieldset elements
		    if (element.nodeName.toLowerCase() == 'fieldset')
			continue;

		    element.onkeydown	= keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    element.onmouseover		= eltMouseOver;
		    element.onmouseout		= eltMouseOut;
		
		    var	name	= element.name;
		    if (name.length == 0)
			name	= element.id;

		    switch(name)
		    {	// element specific actions
			case 'RegDomain':
			{	// domain identifier
			    element.onchange	= changeDomain;
			    break;
			}	// domain identifier

			case 'RegYear':
			{	// put the cursor in the year field
			    element.focus();
			    break;
			}	// put the cursor in the year field

			case 'Stats':
			{	// display statistics
			    element.onclick	= showStats;
			    break;
			}	// display statistics

			case "RegCounty":
			{
			    popupLoading(element);
			    // get the counties information file
			    HTTP.getXML("/Canada/CountiesListXml.php?Domain=" +
						form.RegDomain.value,
					gotCountiesFile,
					noCountiesFile);
			    break;
			}

		    }	// element specific actions
		}	// loop through all elements of a form
    }		// loop through all forms
}		// loadCounties

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this		<form>														*
 ************************************************************************/
function validateForm()
{
    var yearPat	= /^\d{4}$/;
    var numPat	= /^\d{1,6}$/;
    var countPat= /^\d{1,2}$/;

    var	msg	= "";
    if ((document.distForm.RegYear.value.length > 0) && 
		document.distForm.RegYear.value.search(yearPat) == -1)
		msg	+= "Registration Year is not 4 digit number. ";
    if ((document.distForm.RegNum.value.length > 0) &&
		document.distForm.RegNum.value.search(numPat) == -1)
		msg	+= "Registration Number is not a valid number. ";
    if ((document.distForm.Count.value.length > 0) &&
		document.distForm.Count.value.search(countPat) == -1)
		msg	+= "Count is not a 1 or 2 digit number. ";
    if ((document.distForm.BYear.value.length > 0) && 
		document.distForm.BYear.value.search(yearPat) == -1)
		msg	= "Birth Year is not 4 digit number. ";
    if ((document.distForm.Range.value.length > 0) &&
		document.distForm.Range.value.search(countPat) == -1)
		msg	+= "Birth year range is not a 1 or 2 digit number. ";

    if (msg != "")
    {
		alert(msg);
		return false;
    }
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one on reset.			*
 ************************************************************************/
function resetForm()
{
    var	countySelect	= document.distForm.RegCounty;
    changeCounty();	// repopulate Township selection
    return true;
}	// resetForm

/************************************************************************
 *  function gotCountiesFile											*
 *																		*
 *  This method is called when the counties file is retrieved. 			*
 *  It populates the Counties select statement.							*
 *																		*
 *  Parameters:															*
 *		xmlDoc	the counties table as an XML document					*
 ************************************************************************/
function gotCountiesFile(xmlDoc)
{
    var	countySelect	= document.distForm.RegCounty;
    countySelect.options.length	= 0;	// clear the selection
    var newOptions		= xmlDoc.getElementsByTagName("option");

    hideLoading();

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
    countySelect.selectedIndex	= 0;
}		// gotCountiesFile

/************************************************************************
 *  noCountiesFile														*
 *																		*
 *  This method is called if there is no census summary file.				*
 *  The selection list of counties is cleared and an error message		*
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
 *  changeDomain														*
 *																		*
 *  This method is called when the user selects a new domain from		*
 *  the selection list of registration domain identifiers.				*
 *																		*
 *  Input:																*
 *		this		<select name='RegDomain'>								*
 ************************************************************************/
function changeDomain()
{
    // identify the selected county
    var	domainSelect	= this;
    var	form		= this.form;
    var	refElement	= form.RegCounty;
    popupLoading(refElement);
    // get the counties information file
    HTTP.getXML("CountiesListXml.php?Domain=" + this.value,
			gotCountiesFile,
			noCountiesFile);
}		// changeDomain

/************************************************************************
 *  changeCounty														*
 *																		*
 *  This method is called when the user selects a new county from		*
 *  the Counties selection list.										*
 *																		*
 *  Input:																*
 *		this		<select name='RegCounty'>								*
 ************************************************************************/
function changeCounty()
{
    // identify the selected county
    var	countySelect	= this;
    var	optIndex	= countySelect.selectedIndex;
    if (optIndex == -1)		// no entry selected
        noTownship();
    else
    {		// a county has been selected
        var	optVal	= countySelect.options[optIndex].value;
		if (optVal.length > 0)
		{	// have a county code
		    // identify the file containing township information for
		    // the selected county
		    var subFileName	= "/Canada/TownshipsListXml.php?Prov=ON&County=" +
						optVal;
		    if (debug != 'n')
			alert('MarriageRegQuery.js: changeCounty: ' + subFileName);
		    
		    // get the township information file
		    HTTP.getXML(subFileName,
				gotTownship,
				noTownship);

		    popupLoading(document.distForm.RegTownship);
		}	// have a county code
    }		// a county has been selected
}		// changeCounty

/************************************************************************
 *  showStats																*
 *																		*
 *  This function is called when the user clicks on the Stats button.		*
 *  It displays the top level statistics page for Marriage				*
 *  registrations.														*
 *																		*
 *  Input:																*
 *		this				<button id='Stats'>								*
 ************************************************************************/
function showStats()
{
    var	form	= this.form;
    location	= 'MarriageRegStats.php?RegDomain=' + form.RegDomain.value;
    return false;
}		// showStats

