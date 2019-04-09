/************************************************************************
 *  ReqUpdate.js														*
 *																		*
 *  This is the JavaScript code to implement dynamic functionality of	*
 *  the page ReqUpdate.php.												*
 *																		*
 *  History:															*
 *		2010/10/27		improve error response handling					*
 *		2010/11/20		functions changeDiv, getArgs moved to util.js	*
 *		2011/01/22		improve separation of HTML and Javascript		*
 *		2011/06/02		handle IE										*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						function getElementById							*
 *		2012/09/21		pass census id to CensusUpdateStatus.php		*
 *		2013/05/07		use common scripts for all censuses				*
 *		2013/06/11		onchange methods must be invoked as methods		*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2014/09/12		remove use of obsolete selectOptByValue			*
 *		2014/10/14		indices of args array are now lower case		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

    window.onload	= loadDistricts;

/************************************************************************
 *  the census identifier consisting of a 2 character domain identifier	*
 *  and the year of the census											*
 ************************************************************************/
    var	census		= null;

/************************************************************************
 *  function loadDistricts												*
 *																		*
 *  The onload method of the Census Request web page.					*
 *  Request a list of districts in the census as an XML file.			*
 *  If the user is returning from a previous request the province		*
 *  may be specified as a search argument, in which case only the		*
 *  districts for that province are loaded.								*
 *																		*
 *  Input:																*
 *		this			instance of Window								*
 ************************************************************************/
function loadDistricts()
{
    var	provSelect	= null;

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    var	name	= element.name;
		    if (name === undefined || name.length == 0)
				name	= element.id;

		    switch(name)
		    {		// act on specific input element
				case 'Province':
				{
				    var	provSelect	= element;
				    element.onchange	= provChanged;
				    break;
				}	// Province

				case 'District':
				{
				    element.onchange	= districtChanged;
				    break;
				}	// District

				case 'SubDistrict':
				{
				    element.onchange	= subDistChanged;
				    break;
				}	// District

				case 'showForm':
				{
				    element.onclick	= doSubmit;
				    break;
				}	// District

				case 'progress':
				{
				    element.onclick	= showStatus;
				    break;
				}	// District

				case 'Census':
				{
				    census		= element.value;
				    break;
				}	// District

		    }		// act on specific input element
		}	// loop through all elements in form
    }		// loop through all forms

    // act on parameters
    var	provCode	= args["province"];
    var	censusYear	= census.substring(2) - 0;
    if (provCode === undefined && censusYear < 1867)
		provCode	= census.substring(0,2);

    if (provCode === undefined)
    {	// specific province not specified
		// set the Province select element to "All Provinces"
		provSelect.selectedIndex	= 0;

		// Load the list of all districts in the census
		HTTP.getXML("CensusGetDistricts.php?Census=" + census,
				    gotDistFile,
				    noDistFile);
    }	// specific province not specified
    else
    {	// specific province specified
		provSelect.value	= provCode;

		// get the subdistrict information file
		HTTP.getXML("CensusGetDistricts.php?Census=" + census +
							"&Province=" + provCode,
				    gotDistFile,
				    noDistFile);
    }	// specific province specified
}		// loadDistricts

/************************************************************************
 *  function doSubmit													*
 *																		*
 *  This method is invoked when the user clicks on the "submit"			*
 *  button.  The request to obtain a form for updating the specified	*
 *  page of the census is submitted to the server.						*
 *																		*
 *  Input:																*
 *		this			<button name='showForm'>						*
 ************************************************************************/
function doSubmit()
{
    document.distForm.submit();
    return false;
}		// doSubmit

/************************************************************************
 *  function showStatus													*
 *																		*
 *  This method is invoked when the user clicks on the "progress"		*
 *  button.  A web page summarizing the progress of the transcription	*
 *  effort is displayed.												*
 *																		*
 *  Input:																*
 *		this		<button id='progress'>								*
 ************************************************************************/
function showStatus()
{
    var	form	= this.form;
    window.location	= 'CensusUpdateStatus.php?Census=' + census;
    return false;
}		// showStatus

/************************************************************************
 *  function provChanged												*
 *																		*
 *  The onchange method for the Province select element.				*
 *  Take action when the user selects a new province.					*
 *																		*
 *  Input:																*
 *		this			<select name='Province'>						*
 ************************************************************************/
function provChanged()
{
    var	form		= this.form;
    var	provSelect	= this;
    var	optIndex	= provSelect.selectedIndex;
    if (optIndex == -1)
		return;	// nothing to do
    var	optVal	= provSelect.options[optIndex].value;
    loadDistsProv(provSelect, 
				  optVal);		// limit the districts selection
}

/************************************************************************
 *  function loadDistsProv												*
 *																		*
 *  Obtain the list of districts for a specific province				*
 *  in the census as an XML file.										*
 *																		*
 *  Input:																*
 *		provSelect		<select> object									*
 *		prov			two character province code						*
 ************************************************************************/
function loadDistsProv(provSelect, 
					prov)
{
    var	form	= provSelect.form;
    // get the subdistrict information file
    HTTP.getXML("CensusGetDistricts.php?Census=" + census + 
							"&Province=" + prov,
				gotDistFile,
				noDistFile);
}		// loadDistsProv

/************************************************************************
 *  function gotDistFile												*
 *																		*
 *  This method is called when the XML file containing					*
 *  the districts information is retrieved.								*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document from server with districts information	*
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    var	distSelect	= document.distForm.District;
    distSelect.options.length	= 0;	// clear the list

    // create a new HTML Option object representing
    // the default of all districts and add it to the Select
    addOption(distSelect, "All Districts", "");

    try {
    // get the list of districts from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("option");

    // add the options to the Select
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
		if ((value == null) || (value.length == 0))
		{		// cover our ass
		    value		= text;
		}		// cover our ass

		// create a new HTML Option object and add it to the Select
		text += " [dist " + value + "]";
		addOption(distSelect, text, value);
    }			// loop through source "option" nodes
    // if required select a specific district 
    setDist();
    }
    catch(e)
    {
		if (xmlDoc.documentElement)
		    alert("gotSubDist: " + tagToString(xmlDoc.documentElement) + e);
		else
		    alert("gotSubDist: " + xmlDoc + e);
    }
}		// gotDistFile

/************************************************************************
 *  function setDist													*
 *																		*
 *  This method ensures that the District selection matches				*
 *  the value passed in the search arguments.							*
 *																		*
 *  Returns:															*
 *		true if no District was specified, or if it did not match		*
 *				any of the selection items								*
 *		false if it is necessary to load the SubDistrict selection		*
 *				list from the server for a specific District			*
 ************************************************************************/
function setDist()
{
    var	newDistCode	= args["district"];
    if (newDistCode === undefined)
		return true;

    var	distSelect	= document.distForm.District;
    var	distOpts	= distSelect.options;
    for(var i = 0; i < distOpts.length; i++)
    {
		if (distOpts[i].value == newDistCode)
		{	// found matching entry
		    distSelect.selectedIndex	= i;
		    distSelect.onchange();	
		    return false;
		}	// found matching entry
    }	// search for distince to select
    return true;
}		// setDist

/************************************************************************
 *  function noDistFile													*
 *																		*
 *  This method is called if there is no census summary script on the	*
 *  server. The selection list of districts is cleared and an error		*
 *  message is displayed.												*
 ************************************************************************/
function noDistFile()
{
    var	form		= document.distForm;
    var	distSelect	= form.District;
    distSelect.options.length	= 0;	// clear the selection
    var	tdNode		= document.getElementById("msgCell");
    while (tdNode.hasChildNodes())
		   tdNode.removeChild(tdNode.firstChild);
    var	spanElt	= document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className	= "label";
    tdNode.appendChild(spanElt);
    var	msg	= document.createTextNode(
		"Census summary \"CensusGetDistricts.php?Census=" + census +
					"\" failed");
    spanElt.appendChild(msg);
}		// noDistFile

/************************************************************************
 *  function districtChanged											*
 *																		*
 *  The onchange method of the District select element.					*
 *  This method is called when the user selects a new district.			*
 *																		*
 *  Input:																*
 *		this		<select name='District'>							*
 ************************************************************************/  
function districtChanged()
{
    // identify the selected district
    var	distSelect	= this;
    var	form		= this.form;
    var	optIndex	= distSelect.selectedIndex;
    if (optIndex == -1)
		optIndex	= 0;		// default to first entry
    var	optVal		= distSelect.options[optIndex].value;

    // identify the file containing subdistrict information for
    // the selected district
    var subFileName	= "CensusGetSubDists.php?Census=" + census +
							"&District=" + optVal;
    
    // get the subdistrict information file
    HTTP.getXML(subFileName,
				gotSubDist,
				noSubDist);

}		// districtChanged

/************************************************************************
 *  function gotSubDist													*
 *																		*
 *  This method is called when the sub-district information XML			*
 *  document describing a particular district is retrieved.				*
 *																		*
 *  Input:																*
 *		xmlDoc			XML document returned from server				*
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    var	subdistSelect		= document.distForm.SubDistrict;
    subdistSelect.options.length	= 0;	// clear the options
    addOption(subdistSelect,
		      "All Sub-Districts",
		      "");

    try {
		// get the list of subdistricts to select from
		var newOptions		= xmlDoc.getElementsByTagName("option");

		for (var i = 0; i < newOptions.length; ++i)
		{
		    // get the source "option" node
		    // note that although this has the same contents and appearance as
		    // an HTML "option" statement, it is an XML Element object,
		    // not an HTML Option object.
		    var	node	= newOptions[i];
    
		    // get the text value to display to the user
		    var	text	= node.textContent;
    
		    // get the "value" attribute
		    var	value	= node.getAttribute("value");
		    if ((value == null) || (value.length == 0))
		    {		// cover our ass
				value		= text;
		    }		// cover our ass
    
		    // create a new HTML Option object and add it
		    text += " [subdist " + value + "]";
		    var	newOption	= addOption(subdistSelect,
		 			    	    text,
		  		  	    	    value);
		    // make the additional information in the XML Option
		    // available to the application without changing the
		    // appearance of the HTML Option
		    newOption.xmlOption	= node;
   	
		    var	tdNode		= document.getElementById("divCell");
		    while (tdNode.hasChildNodes())
				    tdNode.removeChild(tdNode.firstChild);
		}			// loop through source "option" nodes

		// if required select a specific element in the sub dist list
		setSubDist();
    }	// try
    catch(e)
    {
		if (xmlDoc.documentElement)
		    alert("gotSubDist: " + tagToString(xmlDoc.documentElement));
		else
		    alert("gotSubDist: " + xmlDoc);
    }		// catch
}		// gotSubDist

/************************************************************************
 *  function setSubDist													*
 *																		*
 *  This method ensures that the SubDistrict selection matches			*
 *  the value passed in the search arguments.							*
 ************************************************************************/
function setSubDist()
{
    var	newSubDistCode	= args["subdistrict"];
    if (newSubDistCode === undefined)
		return true;

    var	subDistSelect	= document.distForm.SubDistrict;
    var	distOpts	= subDistSelect.options;
    for(var i = 0; i < distOpts.length; i++)
    {
		if (distOpts[i].value == newSubDistCode)
		{	// found matching entry
		    subDistSelect.selectedIndex	= i;
		    subDistSelect.onchange();	
		    break;
		}	// found matching entry
    }	// search for subDistrict to select

    // select specific division
    var	newDivCode	= args["division"];
    if (newDivCode !== undefined)
    {		// Division identifier supplied
		var	divSelect	= document.distForm.Division;
		if (divSelect !== undefined)
		{	// SubDistrict has a Division select
		    var	divOpts	= divSelect.options;
		    for(var i = 0; i < divOpts.length; i++)
		    {
				if (divOpts[i].value == newDivCode)
				{	// found matching entry
				    divSelect.selectedIndex	= i;
				    changeDiv(divOpts[i].xmlNode);
				    break;
				}	// found matching entry
		    }		// search for division to select
		}	// SubDistrict has a Division select
    }		// Division identifier supplied

    // select specific Page
    var	newPageCode	= args["page"];
    if (newPageCode !== undefined)
    {		// Page identifier supplied
		var	pageSelect	= document.distForm.Page;
		if (pageSelect !== undefined)
		{	// Division has a Page select
		    // first entry (index = 0) is for page 1
		    // so index value is 1 less than page number
		    pageSelect.selectedIndex	= Number(newPageCode) - 1;
		}	// Division has a Page select
    }		// Page identifier supplied
}		// setSubDist

/************************************************************************
 *  function noSubDist													*
 *																		*
 *  This method is called if there is no sub-district					*
 *  script on the server.												*
 ************************************************************************/
function noSubDist()
{
    var	subdistSelect	= document.distForm.SubDistrict;
    subdistSelect.options.length	= 0;	// clear the options
    var	tdNode		= document.getElementById("divCell");
    while (tdNode.hasChildNodes())
		tdNode.removeChild(tdNode.firstChild);
    var	spanElt	= document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className	= "label";
    tdNode.appendChild(spanElt);
    var	msg	= document.createTextNode("No subdistricts defined yet");
    spanElt.appendChild(msg);
}

/************************************************************************
 *  function subDistChanged												*
 *																		*
 *  This is the onchange method of the subdistrict select element.		*
 *  This method is called when the user selects a new sub-district.		*
 *																		*
 *  Input:																*
 *		this			<select name='SubDistrict'>						*
 ************************************************************************/  
function subDistChanged()
{
    // locate cell to display response in
    var	tdNode		= document.getElementById("divCell");
    
    // identify the selected district
    var	subDistSelect	= document.distForm.SubDistrict;
    var	optIndex	= subDistSelect.selectedIndex;
    if (optIndex == -1)
		optIndex	= 0;		// default to first entry
    var	optElt	= subDistSelect.options[optIndex];
    var	optVal	= optElt.value;
    
    // remove any existing HTML from this cell
    while (tdNode.hasChildNodes())
				tdNode.removeChild(tdNode.firstChild);

    // determine how many divisions there are in selected subdist
    var	xmlOpt		= optElt.xmlOption;
    var	divCt		= 0;
    var firstDiv;

    for (var i = 0; i < xmlOpt.childNodes.length; i++)
    {
		var	cNode	= xmlOpt.childNodes[i];
		if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
		{
		    if (!firstDiv)
				firstDiv	= cNode;
		    divCt++;
		}	// element is a "div"
    }		// loop through children of parent

    if (divCt > 1)
    {	// add selections based upon information from XML response
		var select	= tdNode.appendChild(document.createElement("select"));
		select.name	= "Division";
		select.size	= 1;
		select.onchange	= divSelected;

		for (var i = 0; i < xmlOpt.childNodes.length; i++)
		{
		    var	cNode	= xmlOpt.childNodes[i];
		    if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
		    {
				var ident	= cNode.getAttribute("id");
				var newOpt	= addOption(select,
							    "division " + ident,
							    ident);
				newOpt.xmlNode	= cNode;
		    }	// element is a "div"
		}		// loop through children of parent
		// user must select a value
		select.selectedIndex	= 0;
    }	// add selections based upon information from XML response

    // update page prompt in form
    changeDiv(firstDiv);
}		// subDistChanged

/************************************************************************
 *  function divSelected														*
 *																		*
 *  This method is called when the user selects a new division				*
 *																		*
 *  Input:																*
 *		this				<select name='Division'>						*
 ************************************************************************/  
function divSelected()
{
    var select	= this;
    changeDiv(select.options[select.selectedIndex].xmlNode);
}		// divSelected
