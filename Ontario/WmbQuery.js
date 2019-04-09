/************************************************************************
 *  WmbQuery.js															*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  WmbQuery.html														*
 *																		*
 *  History:															*
 *		2013/06/28		created											*
 *		2013/08/04		add facebook status								*
 *		2016/09/24		add button to display statistics by volume		*
 *		2018/01/24		remove gettRightSub								*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.								*
 ************************************************************************/

window.onload	= loadWmb;

/************************************************************************
 *  loadWmb																*
 *																		*
 *  Initialize the dynamic functionality of the script.						*
 ************************************************************************/
function loadWmb()
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
			case "Surname":
			{
			    element.focus();
			    element.onchange	= change;
			    element.checkfunc	= checkName;
			    break;
			}

			case "GivenName":
			case "Father":
			case "Mother":
			case "Minister":
			{
			    element.onchange	= change;
			    element.checkfunc	= checkName;
			    break;
			}

			case "BirthPlace":
			case "BaptismPlace":
			{
			    element.onchange	= change;
			    element.checkfunc	= checkAddress;
			    break;
			}

			case "Volume":
			case "Page":
			{
			    element.onchange	= change;
			    element.checkfunc	= checkNumber;
			    break;
			}

			case "Status":
			{
			    element.onclick	= showStatus;
			    break;
			}

			case "VolStatus":
			{
			    element.onclick	= showVolStatus;
			    break;
			}

		    }	// switch on field name
		}		// loop through all elements in the form
    }		// loop through forms in the page

    // get the districts information file
    //popupLoading(element);
    //HTTP.getXML("WmbDistrictsXml.php",
//		gotDistrictsFile,
//		noDistrictsFile);
}	// function loadWmb

/************************************************************************
 *  validate Form														*
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
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one.						*
 ************************************************************************/
function resetForm()
{
    //var	countySelect	= document.distForm.District;
    //changeDistrict();	// repopulate Area selection
    return true;
}	// resetForm

/************************************************************************
 *  gotDistrictsFile														*
 *																		*
 *  This method is called when the districts file						*
 *  is retrieved.  It populates the selection statement.				*
 ************************************************************************/
function gotDistrictsFile(xmlDoc)
{
    var	countySelect	= document.distForm.District;
    countySelect.options.length	= 0;	// clear the selection
    var newOptions	= xmlDoc.getElementsByTagName("option");

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

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
    countySelect.onchange	= changeDistrict;
    countySelect.selectedIndex	= 0;
}		// gotDistrictsFile

/************************************************************************
 *  noDistrictsFile														*
 *																		*
 *  This method is called if there is no census summary file.				*
 *  The selection list of districts is cleared and an error message		*
 *  displayed.																*
 ************************************************************************/
function noDistrictsFile()
{
    var	tdNode		= document.getElementById("msgCell");

    // create the replacement contents
    // this is essentially cloneNode(true) with symbol substitutions
    var	newCell		= createFromTemplate("noDistrictCell",
						     {},
						     null);

    // clear out everything in the township cell
    while (tdNode.hasChildNodes())
		tdNode.removeChild(tdNode.firstChild);

    // insert the replacement contents
    while (newCell.hasChildNodes())
		tdNode.appendChild(newCell.removeChild(newCell.firstChild));
}		// noDistrictsFile

/************************************************************************
 *  changeDistrict														*
 *																		*
 *  This method is called when the user selects a new district.				*
 ************************************************************************/
function changeDistrict()
{
    // identify the selected county
    var	countySelect	= document.distForm.District;
    var	optIndex	= countySelect.selectedIndex;
//alert("changeDistrict() optIndex=" + optIndex);
    if (optIndex == -1)		// no entry selected
        noArea();
    else
    {
        var	optVal	= countySelect.options[optIndex].value;
        
        // get the township information file
		popupLoading(document.getElementById("TwpCell"));
        HTTP.getXML('WmbAreasXml.php?District=' + encodeURIComponent(optVal),
                gotArea,
                noArea);
    }
}		// changeDistrict

/************************************************************************
 *  gotArea																*
 *																		*
 *  This method is called when the township information XML document		*
 *  relating to a particular district is retrieved.						*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document retrieved from server						*
 ************************************************************************/
function gotArea(xmlDoc)
{
    var	tdNode		= document.getElementById("TwpCell");

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    // create the replacement contents
    // this is essentially cloneNode(true) with symbol substitutions
    var	newCell		= createFromTemplate("chooseAreaCell",
						     {},
						     null);

    // clear out everything in the township cell
    while (tdNode.hasChildNodes())
		tdNode.removeChild(tdNode.firstChild);

    // insert the option entries for the towns, cities and townships in this
    // county from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("option");

    // insert the replacement contents
    while (newCell.hasChildNodes())
    {		// loop through children of cloned template
		var newElt	= newCell.removeChild(newCell.firstChild);
		tdNode.appendChild(newElt);

		if (newElt.nodeType == 1 && newElt.nodeName.toLowerCase() == 'select')
		{	// add options from XML document
		    // provide an alternative name to make the code easier to read
		    var townshipSelect	= newElt;

		    for (var i = 0; i < newOptions.length; ++i)
		    {	// loop through source "option" elements
			// get the source "option" element
			// note that although this has the same contents as an
			// HTML "option" statement, it is an XML Element object,
			// not an HTML Option object.
			var	xmlOptionElt	= newOptions[i];
		
			// get the text value to display to the user
			var	text	= xmlOptionElt.textContent;
		
			// get the "value" attribute
			var	value	= xmlOptionElt.getAttribute("value");
			if ((value == null) || (value.length == 0))
			{		// cover our ass
			    value		= text;
			}		// cover our ass
		
			// create a new HTML Option object and add it 
			addOption(townshipSelect,
				  text,
				  value);
								
		    }	// loop through source "option" elements
		}	// add options from XML document
    }		// loop through children of cloned template
}		// gotArea

/************************************************************************
 *  noArea																*
 *																		*
 *  This method is called if there is no township						*
 *  description file returned by the server.								*
 *  The selection list of townships is replaced by a text input field.		*
 ************************************************************************/
function noArea()
{
//alert("noArea");
    var	tdNode		= document.getElementById("TwpCell");

    // create the replacement contents
    // this is essentially cloneNode(true) with symbol substitutions
    var	newCell		= createFromTemplate("noAreaCell",
						     {},
						     null);

    // clear out everything in the township cell
    while (tdNode.hasChildNodes())
		tdNode.removeChild(tdNode.firstChild);

    // insert the replacement contents
    while (newCell.hasChildNodes())
		tdNode.appendChild(newCell.removeChild(newCell.firstChild));
}		// noArea

/************************************************************************
 *  showStatus																*
 *																		*
 *  Switch to the transcription status page.								*
 *  This function is called when the ShowStatus button is selected.		*
 ************************************************************************/
function showStatus()
{
    location	= "WmbStats.php";
    return false;	// suppress default action
}		// showStatus

/************************************************************************
 *  showVolStatus														*
 *																		*
 *  Switch to the statistics by volume page.								*
 *  This function is called when the ShowVolStatus button is selected.		*
 ************************************************************************/
function showVolStatus()
{
    location	= "WmbVolStats.php";
    return false;	// suppress default action
}		// showVolStatus


