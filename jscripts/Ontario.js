/************************************************************************
 *  Ontario.js															*
 *																		*
 *  Functions used by scripts in the Ontario directory.					*
 *																		*
 *  History:															*
 *		2011/06/24		created											*
 *		2011/08/13		if township name ends in " City", then omit		*
 *						the " City" and the county name from the default*
 *						location name.									*
 *		2011/09/13		IE does not permit modifying type of an <input>	*
 *						node											*
 *		2011/11/21		improve handling of unexpected township value	*
 *		2011/12/27		display loading indicator while waiting for		*
 *						response from server for list of				*
 *						counties/townships								*
 *		2012/01/20		handle missing form in gotCounties				*
 *		2012/02/01		use common popupLoading and hideLoading methods	*
 *		2012/02/13		expand county abbreviations						*
 *		2012/03/17		handle township names ending in ' Town' or		*
 *						' Village'										*
 *		2012/03/26		do not clobber non-empty birthplace or			*
 *						informant residence								*
 *		2012/03/30		use standard DOM functions						*
 *		2012/05/28		create <select id='RegTownship'> with same		*
 *						class as <select name='RegCounty'>				*
 *		2012/07/01		use common routine to determine default			*
 *						location name for both initial load and			*
 *						change township									*
 *						make code more maintainable						*
 *		2014/01/06		support redesign with CSS layout				*
 *		2014/01/10		simplify creating township select				*
 *		2014/01/22		activate mouseover help for township select		*
 *		2014/02/06		initialize more fields on birth registration	*
 *						to default location on township change			*
 *		2014/09/01		add , CA to default locations					*
 *		2014/09/12		remove use of obsolete selectOptByValue			*
 *						marriage place not present in births < 1908		*
 *		2015/07/10		missing var before initialization of newOptions	*
 *		2016/03/07		correct reset of empty marriage registration	*
 *		2017/01/23		update common countyNames object from database	*
 *						update birth place on change township			*
 *		2018/10/06      change gotCountiesFile to leave first option    *
 *		                in place because it is language specific        *
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

// table for expanding county abbreviations to full names
var countyNames	= {
				'Alg'	: 'Algoma District',
				'Brt'	: 'Brant',
				'Bfd'	: 'Brantford City',
				'Bvl'	: 'Brockville City',
				'Bru'	: 'Bruce',
				'Crl'	: 'Carleton',
				'Dfn'	: 'Dufferin',
				'Dnd'	: 'Dundas',
				'Drm'	: 'Durham',
				'Elg'	: 'Elgin',
				'Esx'	: 'Essex',
				'Frn'	: 'Frontenac',
				'Ggy'	: 'Glengarry',
				'Gvl'	: 'Grenville',
				'Gry'	: 'Grey',
				'Hdm'	: 'Haldimand',
				'Hbn'	: 'Haliburton',
				'Htn'	: 'Halton',
				'Hmt'	: 'Hamilton City',
				'Hst'	: 'Hastings',
				'Hrn'	: 'Huron',
				'Knr'	: 'Kenora District',
				'Knt'	: 'Kent',
				'Kng'	: 'Kingston City',
				'Lmt'	: 'Lambton',
				'Lnk'	: 'Lanark',
				'Lds'	: 'Leeds',
				'LaG'	: 'Leeds & Grenville',
				'Lad'	: 'Lennox & Addington',
				'Lcn'	: 'Lincoln',
				'Lon'	: 'London City',
				'Msx'	: 'Middlesex',
				'Msk'	: 'Muskoka District',
				'Nps'	: 'Nippising',
				'Nfk'	: 'Norfolk',
				'Nhm'	: 'Northumberland',
				'Ont'	: 'Ontario',
				'Ott'	: 'Ottawa City',
				'Oxd'	: 'Oxford',
				'Psd'	: 'Parry Sound District',
				'Pel'	: 'Peel',
				'Pth'	: 'Perth',
				'Pbh'	: 'Peterborough',
				'Pst'	: 'Prescott',
				'Pec'	: 'Prince Edward County',
				'Rfw'	: 'Renfrew',
				'Rsl'	: 'Russell',
				'Smc'	: 'Simcoe',
				'Smt'	: 'Stormont',
				'Sud'	: 'Sudbury District',
				'Tkg'	: 'Timiskaming District',
				'Tor'	: 'Toronto City',
				'Vic'	: 'Victoria',
				'Wlo'	: 'Waterloo',
				'Wld'	: 'Welland',
				'Wlt'	: 'Wellington',
				'Wwh'	: 'Wentworth',
				'Win'	: 'Windsor City',
				'Yrk'	: 'York'};

/************************************************************************
 *  function gotCountiesFile											*
 *																		*
 *  This method is called when the counties file for the province		*
 *  is retrieved.  It populates the selection statement.				*
 *																		*
 *  Input:																*
 *		xmlDoc	document containing information on the counties			*
 ************************************************************************/
function gotCountiesFile(xmlDoc)
{
    if (document.distForm)
    {		// form present
		var	countySelect	= document.distForm.RegCounty;
		countySelect.options.length	= 1;	// clear the selection
		var newOptions		= xmlDoc.getElementsByTagName("option");

		// ensure builtin countyNames array matches database
		countyNames		= [];	// empty out pre-defined contents

		// add options corresponding to the elements in the XML file
		for (var i = 0; i < newOptions.length; ++i)
		{
		    // get the source "option" node
		    // note that although this has the same contents and appearance
		    // as an HTML "option" statement, it is an XML Element object,
		    // not an HTML Option object.
		    var	node	= newOptions[i];

		    // get the text value to display to the user
		    var	text	= node.textContent;

		    // get the "value" attribute
		    var	value	= node.getAttribute("value");
		    if ((value == null) || (value.length == 0))
		    {		// cover our ass
			value	= text;
		    }		// cover our ass

		    // create a new HTML Option object and add it to the Select
		    var	newOption	= addOption(countySelect,
								text,
								value);

		    // ensure builtin countyNames array matches database
		    countyNames[value]	= text;
		}			// loop through source "option" nodes

		// specify the action for selecting a county
		countySelect.onchange	= changeCounty;
		var	countyTxt	= document.distForm.RegCountyTxt;

		if (countyTxt)
		{		// county code text field in basic form
		    // select the matching entry in the select list
		    var	countyCode	= countyTxt.value;
		    if (countyCode.length > 0)
		    {		// a county has been specified
				countySelect.value	= countyCode;

				if (countySelect.selectedIndex == 0)
				{		// no match
				    // make the text field visible
				    countyTxt.type	= "text";
				}		// no match
				else
				    changeCounty();	// populate township list
		    }		// a county has been specified
		    else
				countySelect.selectedIndex	= 0;
		}		// county code text field in basic form
    }		// form present
}		// gotCountiesFile

/************************************************************************
 *  function noCountiesFile												*
 *																		*
 *  This method is called if there is no counties file.					*
 *  The selection list of counties is cleared and						*
 *  an error message displayed.											*
 ************************************************************************/
function noCountiesFile()
{
    var	countySelect	= document.distForm.RegCounty;
    countySelect.options.length	= 0;	// clear the selection
    var	tableNode	= document.getElementById("formTable");
    var	tbNode		= tableNode.tBodies[0];
    var	trNode		= document.getElementById("distRow");
    var	tdNode		= document.getElementById("msgCell");
    while (tdNode.hasChildNodes())
		tdNode.removeChild(tdNode.firstChild);
    tdNode.setAttribute("class", "label");
    tdNode.className	= "label";
    var	msg		= document.createTextNode("Counties listing missing");
    spanElt.appendChild(msg);
}		// noCountiesFile

var	defaultPlaceName	= '';

/************************************************************************
 *  function changeCounty												*
 *																		*
 *  This method is called when the user selects a new county.			*
 *  It requests that the list of townships be updated accordingly.		*
 *																		*
 *  Input:																*
 *		this	<select name='RegCounty'>								*
 ************************************************************************/
function changeCounty()
{
    // identify the selected county
    var	form		= document.distForm;

    // check for the default place name in an uninitialized form
    for(var i = 0; i < form.elements.length; i++)
    {		// loop through all input elements
		var	element	= form.elements[i];
		switch(element.name)
		{	// look for a field with default place name
		    case 'InfRes':
		    case 'InformantRes':
		    {
				if (element.value.length > 0)
				{
				    defaultPlaceName	= element.value;
				}
				break;
		    }
		}	// look for a field with default place name
    }		// loop through all input elements

    var	countySelect	= form.RegCounty;
    var	optIndex	= countySelect.selectedIndex;
    if (optIndex == -1)		// no county selected
		noTownship();		// clear township list
    else
    {		// county selected
		var	optVal	= countySelect.options[optIndex].value;
		if (optVal.length > 0)
		{	// have a county code
		    // identify the file containing township information for
		    // the selected county
		    var subFileName	= "/Canada/TownshipsListXml.php?Prov=ON&County=" +
								optVal;
		    //alert("changeCounty: subFileName=\"" + subFileName + "\"");

		    // get the township information file
		    if (debug != 'n')
				alert('Ontario.js: changeCounty: ' + subFileName);
		    HTTP.getXML(subFileName,
						gotTownship,
						noTownship);

		    popupLoading(document.distForm.RegTownship);
		}	// have a county code
    }		// county selected
}		// changeCounty

/************************************************************************
 *  function getDefaultLocation											*
 *																		*
 *  This method is determines the default location string based upon the*
 *  chosen county and township.											*
 *																	    *
 *  Input:																*
 *		countyCode		3 character abbreviation for county				*
 *		twpCode			unique identifier for township within county	*
 *																	    *
 *	Returns:														    *
 *		string location in standard format,                             *
 *		for example: Township, County, ST, CC						    *
 ************************************************************************/
function getDefaultLocation(county, township)
{
    if (countyNames[county])
		county		= countyNames[county];

    if (township.substring(township.length - 5) == ' City')
		return township.substring(0, township.length - 5) + ", ON, CA";
    else
    if (township.substring(township.length - 4) == ' Twp')
		return township.substring(0, township.length - 4) + ", " +
						      county + ", ON, CA";
    else
    if (township.substring(township.length - 5) == ' Twp.' ||
		township.substring(township.length - 5) == ' Town')
		return township.substring(0, township.length - 5) + ", " +
						      county + ", ON, CA";
    else
    if (township.substring(township.length - 8) == ' Village')
		return township.substring(0, township.length - 8) + ", " +
						      county + ", ON, CA";
    else
		return township + ", " + county + ", ON, CA";
}		// getDefaultLocation

/************************************************************************
 *  function gotTownship												*
 *																		*
 *  This method is called when the township information XML document	*
 *  relating to a particular county is retrieved.						*
 *																		*
 *  Input:																*
 *		xmlDoc		document containing information on the townships	*
 ************************************************************************/
function gotTownship(xmlDoc)
{
    // hide the loading indicator
    hideLoading();
    if (debug != 'n')
    {
		if (xmlDoc)
		    alert('Ontario.js: noTownship: ' + tagToString(xmlDoc));
		else
		    alert('Ontario.js: noTownship: xmlDoc is null');
    }

    var	regTownship	= document.getElementById("RegTownship");
    var	regCounty	= document.getElementById("RegCounty");
    var	form		= regTownship.form;
    var	parentNode	= regTownship.parentNode;
    var	townshipSelect	= null;

    if (regTownship &&
		regTownship.nodeType == 1 &&
		regTownship.nodeName.toLowerCase() == "select")
    {			// already have select element
		townshipSelect			= regTownship;
		townshipSelect.options.length	= 0;
    }			// already have select element
    else
    {			// need a select element
		var	nextElement	= null;
		if (regTownship)
		{
		    nextElement	= regTownship.nextSibling;
		    parentNode.removeChild(regTownship);
		}
		// create a new select element
		townshipSelect		= document.createElement("select");
		townshipSelect.name	= "RegTownship";
		townshipSelect.id	= "RegTownship";
		townshipSelect.className= regCounty.className;
		townshipSelect.size	= 1;
 	parentNode.insertBefore(townshipSelect, nextElement);
		townshipSelect.onmouserover	= eltMouseOver;
		townshipSelect.onmouserout	= eltMouseOut;
    }			// need a select element

    // create a new HTML Option object to represent the null selection
    // and add it to the selection
    var	newOption	= addOption(townshipSelect,
							    "Choose a Township",
							    "");

    // insert the option entries for the towns, cities and townships in this
    // county from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("option");

    //alert("newOptions.length=" + newOptions.length);

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
		var	newOption	= addOption(townshipSelect,
								    text,
								    value);
    }			// loop through source "option" nodes

    // if the form has a township value, select it
    townshipSelect.selectedIndex	= 0;
    var	option;
    var regTownshipTxt	= document.getElementById("RegTownshipTxt");
    if (regTownshipTxt)
    {			// have a township text field in form
		var townshipName	= regTownshipTxt.value;
		townshipSelect.value	= townshipName;
		var index		= townshipSelect.selectedIndex;
		option			= townshipSelect.options[index];
		if (option.text == '')
		{		// township not found in selection list
		    option.text		= townshipName;
		}		// township not found in selection list

		var	dfltValue	= getDefaultLocation(form.RegCounty.value,
									     townshipName);
		for(var i = 0; i < form.elements.length; i++)
		{		// loop through all input elements
		    var	element	= form.elements[i];
		    switch(element.name)
		    {		// act on specific named element
				case 'Place':
				case 'GResidence':
				case 'GBirthPlace':
				case 'Witness1Res':
				case 'BResidence':
				case 'BBirthPlace':
				case 'Witness2Res':
				case 'BirthPlace':
				case 'InfRes':
				{	// locations
				    if (element.value == '')
						element.value	= dfltValue;
				}	// locations
		    }		// act on specific named element
		}		// loop through all input elements
    }			// have a township text field in form

    // intercept changes to township
    parentNode.appendChild(townshipSelect);
    townshipSelect.onchange	= changeTownship;
}		// gotTownship

/************************************************************************
 *  function noTownship													*
 *																		*
 *  This method is called if no county has been chosen or				*
 *  there is no township description file.								*
 *  The selection list of townships is replaced by a text				*
 *  input field.														*
 ************************************************************************/
function noTownship()
{
    // hide the loading indicator
    hideLoading();
    if (debug != 'n')
		alert('Ontario.js: noTownship');

    // clear out old selection list in the township cell
    var	element		= document.getElementById("RegTownship");
    if (element)
    {
		var	container	= element.parentNode;
		var	form		= element.form;
		container.removeChild(element);

		// the field with name RegTownshipTxt
		// contains the name of the township from the database record
		var	twpInput	= document.createElement("input");
		twpInput.type		= "text";
		if (form.RegTownshipTxt)
		    twpInput.value	= form.RegTownshipTxt.value;
		twpInput.name		= "RegTownship";
		twpInput.id		= "RegTownship";
		twpInput.classname	= "act left";
		twpInput.size		= 24;
		container.appendChild(twpInput);
    }
}		// noTownship

/************************************************************************
 *  function changeTownship												*
 *																		*
 *  This method is called when the user selects a new township.			*
 *																		*
 *  Input:																*
 *		this		<select name='RegTownship'>							*
 ************************************************************************/
function changeTownship()
{
    // identify the selected township
    var	townshipSelect	= this;
    var	theForm		= this.form;
    var	optIndex	= townshipSelect.selectedIndex;
    if (optIndex == -1)		// no township selected
		noTownship();		// clear township list
    else
    {		        // township selected
		var	town		= townshipSelect.options[optIndex].value;
		theForm.RegTownshipTxt.value	= town;
		var	dfltValue	= getDefaultLocation(theForm.RegCounty.value,
									     town);
		var	twpCell		= document.getElementById("TwpCell");

		// if the form has not been initialized from a record
		// fill in defaults for a number of fields
		var	nonames	=
		    (theForm.GGivenNames && theForm.GGivenNames.value.length == 0 &&
		     theForm.GSurname && theForm.GSurname.value.length == 0 &&
		     theForm.BGivenNames && theForm.BGivenNames.value.length == 0 &&
		     theForm.BSurname && theForm.BSurname.value.length == 0) ||
		    (theForm.GivenNames && theForm.GivenNames.value.length == 0 &&
		     theForm.Surname && theForm.Surname.value.length == 0);
		if (nonames && theForm.Witness1Res)
		    defaultPlaceName	= theForm.Witness1Res.value;

		for(var i = 0; i < theForm.elements.length; i++)
		{		    // loop through all input elements
		    var	element	= theForm.elements[i];
		    switch(element.name)
		    {	    // act on specific named element
				case 'RegTownshipTxt':
				{	// township name
				    element.value	= town;
				    break;
				}	// township name

				case 'Place':
				case 'BirthPlace':
				case 'InformantRes':
				case 'GResidence':
				case 'GBirthPlace':
				case 'Witness1Res':
				case 'BResidence':
				case 'BBirthPlace':
				case 'Witness2Res':
				case 'PhysAddr':
				case 'InfRes':
				case 'BurPlace':
				case 'UndertkrAddr':
				case 'FatherOccPlace':
				case 'MotherOccPlace':
				{	// location field
				    if (element.value == '' ||
						element.value.substring(0,1) == ',' ||
						element.value == defaultPlaceName)
						element.value	= dfltValue;
				    break;
				}	// location field

				case 'MarriagePlace':
				{	// location field
				    if (element.value != '' &&
						element.value != defaultPlaceName)
						break;
				    var fileOff	= location.pathname.lastIndexOf("/") +1;
				    var pageName	= location.pathname.substring(fileOff);
				    if (pageName.substring(0, 5) == 'Birth' &&
						(theForm.RegYear.value - 0) < 1908)
						element.value	= 'N/A';
				    else
						element.value	= dfltValue;
				    break;
				}	// location field
		    }	    // act on specific named element
		}		    // loop through all input elements
    }			    // township selected
}		// function changeTownship
