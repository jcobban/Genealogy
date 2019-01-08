/************************************************************************
 *  ReqUpdatePages.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  ReqUpdatePages.html													*
 *																		*
 *  History:															*
 *		2011/03/14		created											*
 *		2011/11/04		support mouseover help							*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2013/07/30		defer facebook initialization until after load	*
 *						standardize initialization of form				*
 *						activate mouse over help for division selection	*
 *		2013/08/25		use pageInit common function					*
 *		2014/09/12		remove use of obsolete selectOptByValue			*
 *		2014/10/14		indices of args array are now lower case		*
 *		2015/08/12		values of <select name='Census'> are now		*
 *						full 6 character census identifier				*
 *		2016/03/16		adjust value of Census parameter to PageForm.php*
 *						for 1851 and 1861 censuses to include province	*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

    window.onload	= onloadPages;

/************************************************************************
 *  onloadPages																*
 *																		*
 *  The onload method of the request to update the Pages table web page.*
 *  If the user is returning from a previous request the province.		*
 *  may be specified as a search argument, in which case only the		*
 *  districts for that province are loaded.								*
 ************************************************************************/
function onloadPages()
{
    // perform common page initialization
    pageInit();

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];
		    element.onkeydown	= keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    var	name	= element.name;
		    if (!name || name.length == 0)
				name	= element.id;

		    switch(name)
		    {		// act on specific elements
				case "CensusSel":
				{
				    element.onchange		= changeCensus;
				    element.onchange();
				    break;
				}	// Census
		
				case "Province":
				{
				    element.onchange		= changeProv;
				    break;
				}	// Province
		
				case "District":
				{
				    element.onchange		= changeDist;
				    break;
				}	// District
		
				case "SubDistrict":
				{
				    element.onchange		= changeSubDist;
				    break;
				}	// SubDistrict

		    }		// act on specific elements
		}	// loop through elements in form
    }		// iterate through all forms
}		// onloadPages

/************************************************************************
 *  changeCensus														*
 *																		*
 *  The onchange method of the Census selection.						*
 *  The user has selected a specific census.  Initiate the load				*
 *  of the associated districts file.										*
 ************************************************************************/
function changeCensus()
{
    var	censusSelect		= this;
    var	form			= this.form;
    var	censusOptions		= this.options;
    var	census;

    if (this.selectedIndex >= 0)
    {			// option chosen
		var currCensusOpt	= censusOptions[this.selectedIndex];
		var census		= currCensusOpt.value;
		if (census.length > 0)
		{		// non-empty option chosen
		    form.Census.value		= census; 
		    var censusYear		= census.substring(2);
		    var	provSelect		= document.distForm.Province;
		    provSelect.options.length	= 0;	// clear the list
		    switch(censusYear)
		    {			// act on census year
				case "1831":
				{		// pre-confederation
				    addOption(provSelect,	"Quebec",	"QC");
				    provSelect.selectedIndex	= 0;
				    break;
				}

				case "1851":
				case "1861":
				{		// pre-confederation
				    addOption(provSelect,	"Canada East (Quebec)",	"CE");
				    addOption(provSelect,	"Canada West (Ontario)","CW");
				    addOption(provSelect,	"New Brunswick",	"NB");
				    addOption(provSelect,	"Nova Scotia",		"NS");
				    addOption(provSelect,	"Prince Edward Island",	"PI");
				    provSelect.selectedIndex	= 1;
				    break;
				}		// pre-confederation

				case "1906":
				case "1916":
				{		// prairie provinces
				    addOption(provSelect,	"All Provinces",	"");
				    addOption(provSelect,	"Alberta",		"AB");
				    addOption(provSelect,	"Manitoba",		"MB");
				    addOption(provSelect,	"Saskatchewan",		"SK");
				    provSelect.selectedIndex	= 0;
				    break;
				}		// prairie provinces

				case "1871":
				case "1881":
				case "1891":
				case "1901":
				{		// post-confederation
				    addOption(provSelect,	"All Provinces",	"");
				    addOption(provSelect,	"British Columbia",	"BC");
				    addOption(provSelect,	"Manitoba",		"MB");
				    addOption(provSelect,	"New Brunswick",	"NB");
				    addOption(provSelect,	"Nova Scotia",		"NS");
				    addOption(provSelect,	"North-West Territories","NT");
				    addOption(provSelect,	"Ontario",		"ON");
				    addOption(provSelect,	"Prince Edward Island",	"PI");
				    addOption(provSelect,	"Quebec",		"QC");
				    provSelect.selectedIndex	= 0;
				    break;
				}		// post-confederation

				case "1911":
				case "1921":
				{		// post-confederation
				    addOption(provSelect,	"All Provinces",	"");
				    addOption(provSelect,	"Alberta",		"AB");
				    addOption(provSelect,	"British Columbia",	"BC");
				    addOption(provSelect,	"Manitoba",		"MB");
				    addOption(provSelect,	"New Brunswick",	"NB");
				    addOption(provSelect,	"Nova Scotia",		"NS");
				    addOption(provSelect,	"North-West Territories","NT");
				    addOption(provSelect,	"Ontario",		"ON");
				    addOption(provSelect,	"Prince Edward Island",	"PI");
				    addOption(provSelect,	"Quebec",		"QC");
				    addOption(provSelect,	"Saskatchewan",		"SK");
				    provSelect.selectedIndex	= 0;
				    break;
				}		// post-confederation
		    }			// act on census year

		    // check for province passed as a parameter
		    var	province	= form.ProvinceCode.value;

		    if (province && (province.length > 0))
		    {		// province specified in invocation
				provSelect.value	= province;
		    }		// province specified in invocation
		    else
		    {		// default 
				var provOpts	= provSelect.options;
				province	= provOpts[provSelect.selectedIndex].value;
		    }		// default
		    loadDistsProv(province);	// load districts
		}		// non-empty census chosen 
    }			// option chosen

}		// changeCensus

/************************************************************************
 *  changeProv																*
 *																		*
 * The onchange method for the Province select element.						*
 * Take action when the user selects a new province.						*
 ************************************************************************/
function changeProv()
{
    var	provSelect	= this;
    var	form		= this.form;
    var	census		= form.Census.value;
    var	censusYear	= census.substring(2);
    var	optIndex	= provSelect.selectedIndex;
    if (optIndex == -1)
		return;	// nothing to do
    var	optVal	= provSelect.options[optIndex].value;
    if (censusYear == '1851' || censusYear == '1861')
		form.Census.value	= optVal + censusYear;
    loadDistsProv(optVal);		// limit the districts selection
}

/************************************************************************
 *  loadDistsProv														*
 *																		*
 *  Obtain the list of districts for a specific province				*
 *  in the 1871 census as an XML file.										*
 *																		*
 *  Input:																*
 *		prov		two character province code								*
 ************************************************************************/

function loadDistsProv(prov)
{
    var	censusSelect	= document.distForm.Census;
    var	census		= censusSelect.value;
    var	censusYear	= census.substring(2);
    var	xmlName;
    if (censusYear < "1871")
    {			// pre-confederation
		xmlName	= "CensusGetDistricts.php?Census=" + prov + censusYear;
    }			// pre-confederation
    else
    {			// post-confederation
		xmlName	= "CensusGetDistricts.php?Census=" + census +
					"&Province=" + prov;
    }			// post-confederation
    // get the district information file	
    HTTP.getXML(xmlName,
				gotDistFile,
				noDistFile);
}		// loadDistsProv

/************************************************************************
 *  gotDistFile																*
 *																		*
 *  This method is called when the XML file containing						*
 *  the districts information is retrieved from the server.				*
 *																		*
 *  Input:																*
 *		xmlDoc		XML from server with districts information				*
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    if(!xmlDoc)
    {
		alert("ReqUpdatePages.js: gotDistFile: unable to retrieve districts file: " + xmlName);
		return;
    }

    var	distSelect	= document.distForm.District;
    distSelect.options.length	= 0;	// clear the list

    // create a new HTML Option object representing
    // the default of all districts and add it to the Select
    addOption(distSelect, "Select a District", "");

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
    DistSet();
}		// gotDistFile

/************************************************************************
 *  DistSet																*
 *																		*
 *  This method ensures that the District selection matches				*
 *  the value passed in the search arguments.								*
 *																		*
 *  Returns:																*
 *		true if no District was specified, or if it did not match		*
 *				any of the selection items								*
 *		false if it is necessary to load the SubDistrict selection		*
 *				list from the server for a specific District				*
 ************************************************************************/
function DistSet()
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
		    changeDist();	
		    return false;
		}	// found matching entry
    }	// search for distince to select
    return true;
}		// distSet

/************************************************************************
 *  noDistFile																*
 *																		*
 *  This method is called if there is no census summary		file from the		*
 *  server.																*
 *  The selection list of districts is cleared and an error message		*
 *  displayed.																*
 ************************************************************************/
function noDistFile()
{
    var	distSelect	= document.distForm.District;
    distSelect.options.length	= 0;	// clear the selection
    var	tableNode	= getElt(document.distForm, "TABLE");
    var tbNode		= getElt(tableNode,"TBODY");
    var	trNode		= document.getElementById("distRow");
    var	tdNode		= document.getElementById("msgCell");
    while (tdNode.hasChildNodes())
		   tdNode.removeChild(tdNode.firstChild);
    var	spanElt	= document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className	= "label";
    tdNode.appendChild(spanElt);
    var	msg	= document.createTextNode(
		"Census summary \"CensusGetDistricts.php?Census=CW1871\" failed");
    spanElt.appendChild(msg);
}		// noDistFile

/************************************************************************
 *  changeDist																*
 *																		*
 *  This is the onchange method of the District select element.				*
 *  This method is called when the user selects a new district.				*
 ************************************************************************/
function changeDist()
{
    // identify the selected census
    var	form		= document.distForm;
    var	censusSelect	= form.CensusSel;
    var census		= censusSelect.value;
    var	censusYear	= census.substring(2);

    // identify the selected district
    var	distSelect	= form.District;
    var	optIndex	= distSelect.selectedIndex;
    if (optIndex < 1)
		return;		// no district selected
    var	distId	= distSelect.options[optIndex].value;

    // identify the file containing subdistrict information for
    // the selected district
    var subFileName;
    var	provSelect;
    var	provId;

    if (censusYear > 1867)
    {		// post-confederation, one census for all of Canada
		subFileName	= "CensusGetSubDists.php?Census=" + census +
						"&District=" + distId;
    }		// post-confederation, one census for all of Canada
    else
    {		// pre-confederation, separate census for each colony
		provSelect	= form.Province;
		optIndex	= provSelect.selectedIndex;
		if (optIndex < 0)
		    return;		// no colony selected
		provId	= provSelect.options[optIndex].value;
		subFileName	= "CensusGetSubDists.php?Census=" +
						provId + censusYear +
						"&District=" + distId;
    }		// pre-confederation, separate census for each colony
    // get the subdistrict information file
    //alert("ReqUpdatePages.js: changeDist: subFileName=" + subFileName);
    HTTP.getXML(subFileName,
				gotSubDist,
				noSubDist);

}		// changeDist

/************************************************************************
 *  gotSubDist																*
 *																		*
 *  This method is called when the sub-district information XML				*
 *  document relating to a particular district is retrieved from the		*
 *  server.																*
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    var	subdistSelect	= document.distForm.SubDistrict;
    subdistSelect.options.length	= 0;	// clear the selection
    addOption(subdistSelect,
		      "All Sub-Districts",
		      "");

    //alert("ReqUpdatePages.js: gotSubDist: xmlDoc=" + tagToString(xmlDoc));
    // get the list of subdistricts to select from
    var newOptions		= xmlDoc.getElementsByTagName("option");

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

		// create a new HTML Option object and add it
		text += " [subdist " + value + "]";
		var	newOption	= addOption(subdistSelect,
		 				    text,
		  		  		    value);
		// make the additional information in the XML Option
		// available to the application without changing the
		// appearance of the HTML Option
		newOption.xmlOption	= node;
   	
		var	tableNode	= getElt(document.distForm, "TABLE");
		var	tbNode		= getElt(tableNode,"TBODY");
		var	trNode		= document.getElementById("divRow");
		var	tdNode		= document.getElementById("divCell");
		if (!tdNode)
		    alert("ReqUpdatePages.js: gotSubDist: trNode=" +
				  tagToString(trNode));
		else
		while (tdNode.hasChildNodes())
				tdNode.removeChild(tdNode.firstChild);
    }			// loop through source "option" nodes

    // if required select a specific element in the sub dist list
    subDistSet();
}		// gotSubDist

/************************************************************************
 *  subDistSet																*
 *																		*
 *  This method ensures that the SubDistrict selection matches				*
 *  the value passed in the search arguments.								*
 ************************************************************************/
function subDistSet()
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
		    changeSubDist();	
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
		    pageSelect.selectedIndex	= Number(newPageCode) - 1;
		}	// Division has a Page select
    }		// Page identifier supplied
}		// subDistSet


/************************************************************************
 *  noSubDist																*
 *																		*
 *  This method is called if there is no sub-district						*
 *  description to return.												*
 ************************************************************************/
function noSubDist()
{
    var	subdistSelect	= document.distForm.SubDistrict;
    subdistSelect.options.length	= 0;	// clear the selection
    var	tableNode	= getElt(document.distForm, "TABLE");
    var tbNode		= getElt(tableNode,"TBODY");
    var	trNode		= document.getElementById("divRow");
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
 *  changeSubDist														*
 *																		*
 *  This method is called when the user selects a new sub-district.		*
 ************************************************************************/
function changeSubDist()
{
    // locate cell to display response in
    var	tableNode	= getElt(document.distForm, "TABLE");
    var	tbNode		= getElt(tableNode,"TBODY");
    var	trNode		= document.getElementById("divRow");
    var	tdNode		= document.getElementById("divCell");
    
    // identify the selected district
    var	subDistSelect	= document.distForm.SubDistrict;
    var	optIndex	= subDistSelect.selectedIndex;
    if (optIndex == -1)
		optIndex	= 0;		// default to first entry
    var	optElt	= subDistSelect.options[optIndex];
    var	optVal	= optElt.value;
    //alert("ReqUpdatePages.js: changeSubDist: optIndex=" + optIndex + ", optElt=" + optElt.outerHTML + ", xmlOption=" + tagToString(optElt.xmlOption));
    
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
		actMouseOverHelp(select);
		//select.onchange	= divSelected;

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
}		// changeSubDist

/************************************************************************
 *  showForm																*
 *																		*
 *  Show the form for editting the Pages table.								*
 ************************************************************************/
function showForm()
{
    document.distForm.submit();
}		// showForm
