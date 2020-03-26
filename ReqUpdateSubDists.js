/************************************************************************
 *  ReqUpdateSubDists.js												*
 *																		*
 *  Implement dynamic functionality the the web page to select			*
 *  a district of the subdistrict table to be editted.					*
 *																		*
 *  History:															*
 *		2010/11/20		function getArgs moved to util.js				*
 *		2011/03/09		improve separation of HTML and Javascript		*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2012/09/17		support census identifiers						*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2013/08/27		handle incomplete census identified				*
 *		2014/10/14		indices of args array are now lower case		*
 *		2015/03/15		missing support for 1921 census					*
 *						clean up comments								*
 *						display popup message for dist file error		*
 *						internationalize all text strings including		*
 *						province names									*
 *		2015/06/02		invoked from ReqUpdateSubDists.php				*
 *		2016/09/27		check for presence of arg before using			*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/03/08      fix infinite loop                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

// define the function to be called when the page is loaded
window.onload	= loadDistricts;

// table translating province codes to province names
var provinceNames	= [];

/************************************************************************
 *  loadDistricts														*
 *																		*
 *  The onload method of the Sub Districts request web page.			*
 *  If the user is returning from a previous request the province		*
 *  may be specified as a search argument, in which case only the		*
 *  districts for that province are loaded.								*
 *																		*
 *  Input:																*
 *		this			window                     						*
 *		ev              Javascript load Event                           *
 ************************************************************************/
function loadDistricts(ev)
{
    // load names of provinces
    var	divs	= document.getElementsByTagName('div');
    for(var di = 0; di < divs.length; di++)
    {			// loop through all <div>s
		var	element	= divs[di];
		var	id	= element.id;
		if ((id.length == 4 || id.length == 6) &&
		    id.substring(0,4) == 'prov')
		{
		    provinceNames[id.substring(4)]	= element.innerHTML.trim();
		}
    }			// loop through all <div>s

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	    = document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	        = form.elements[j];

		    element.onkeydown	= keyDown;
		    var	name	        = element.name;
		    if (!name || name.length == 0)
				name	        = element.id;
		    switch(name)
		    {	// act on specific elements
				case "CensusYear":
				{
				    element.selectedIndex	= 0;
				    element.onchange		= changeCensus;
				    // check for census identifier passed as a parameter
				    var	censusId	        = form.censusId.value;
				    if (censusId !== undefined)
				    {
						if (censusId.length == 4)
						    censusId	    = "CA" + censusId;
						var	censusOpts		= element.options;
						for(var k = 0; k < censusOpts.length; k++)
						{
						    if (censusOpts[k].value == censusId)
						    {	// found matching entry
							    element.selectedIndex	= i;
							    element.onchange();
							    break;
						    }	// found matching entry
						}	// search for census to select
				    }		// Census identifier passed
				    break;
				}	// CensusYear

				case "Province":
				{
				    element.onchange		= changeProv;
				    break;
				}	// CensusYear

				case "District":
				{
				    element.onchange		= changeDist;
				    element.ondblclick		= showForm;
				    break;
				}	// CensusYear

		    }	    // act on specific elements
		}	        // loop through elements in form
    }		        // loop through all forms

}		// function loadDistricts

/************************************************************************
 *  function changeCensus												*
 *																		*
 *  The method called when the Census selection changes.				*
 *																		*
 *  Input:																*
 *		this			<select name='CensusYear'>						*
 *		ev              Javascript change Event                         *
 ************************************************************************/
function changeCensus(ev)
{
    var	censusSelect		= this;
    var	censusOptions		= this.options;
    var censusElt		= document.distForm.Census;
    // province passed as a parameter
    var	province		= ''
    if ('province' in args)
		province		= args['province'];
    var	censusId;

    if (this.selectedIndex >= 0)
    {			// option chosen
		var currCensusOpt	= censusOptions[this.selectedIndex];
		var censusId		= currCensusOpt.value;

		if (censusId.length > 0)
		{		// non-empty option chosen
		    censusElt.value		= censusId;
		    var	provSelect		= document.distForm.Province;
		    provSelect.options.length	= 0;	// clear the list
		    var provInfo	= document.censusinfo.elements['Provinces' + censusId].value;
		    if (censusId == "CA1851" || censusId == "CA1861")
		    {		// pre-confederation
				if (province.length == 0)
				    province		= 'CW';
		        if (provInfo.indexOf(province) == -1)
				    province		= 'CW';
				censusElt.value		= province + censusId.substring(2);
		    }		// pre-confederation
		    else
		    {		// post-confederation
				provSelect.selectedIndex	= 0;
		        if (provInfo.indexOf(province) == -1)
				    province		= '';
				addOption(provSelect,	'Choose Province', '');
		    }		// post-confederation
		    for (var pi = 0; pi < provInfo.length; pi += 2)
		    {
				var provCode		= provInfo.substr(pi, 2);
				addOption(provSelect,	provinceNames[provCode],provCode);
		    }		// loop through provinces
		    provSelect.value	= province;

		    // act on province passed as a parameter
		    if (province.length > 0)
		    {		// province supplied
				provSelect.value	= province;
		    }		// province supplied

		    // update districts 
		    loadDistsProv(province);	// load districts
		}		// non-empty census chosen 
    }			// option chosen

}		// function changeCensus

/************************************************************************
 *  function changeProv													*
 *																		*
 *  Take action when the user selects a new province.					*
 *																		*
 *  Input:																*
 *		this			<select name='Province'>						*
 *		ev              Javascript change Event                         *
 ************************************************************************/
function changeProv()
{
    var	provSelect	= this;
    var	optIndex	= provSelect.selectedIndex;
    if (optIndex == -1)
		return;	// nothing to do
    var	province	= provSelect.options[optIndex].value;
    var	censusId	= document.distForm.Census.value;
    var censusYear	= censusId.substring(2);
    if (censusYear < "1867")
		document.distForm.Census.value = province + censusYear;
    loadDistsProv(province);		// limit the districts selection
}		// function changeProv

/************************************************************************
 *  function loadDistsProv												*
 *																		*
 *  Obtain the list of districts for a specific province				*
 *  in the census as an XML file.										*
 *																		*
 *  Input:																*
 *		prov		two character province code							*
 ************************************************************************/
function loadDistsProv(prov)
{
    var	censusId	= document.distForm.Census.value;
    var	censusYear	= censusId.substring(2);

    // get the district information file	
    HTTP.getXML("CensusGetDistricts.php?Census=" + censusId +
					"&Province=" + prov,
				gotDistFile,
				noDistFile);
}		// function loadDistsProv

/************************************************************************
 *  function gotDistFile												*
 *																		*
 *  This method is called when the XML file containing					*
 *  the districts information is retrieved.								*
 *																		*
 *  Input:																*
 *		xmlDoc		XML from server with districts information			*
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    var	distSelect	= document.distForm.District;
    var	rootNode	= xmlDoc.documentElement;
    var msgs		= xmlDoc.getElementsByTagName("msg");
    if (msgs.length > 0)
    {		// error messages
		var	alertTxtElt	= document.getElementById('badDistFileMsg');
		var	alertTxt	= alertTxtElt.innerHTML.replace('$msgs',
									msgs[0].textContent);
		popupAlert(alertTxt,
				   distSelect);
		return;
    }		// error messages

    distSelect.options.length	= 0;	// clear the list

    // create a new HTML Option object representing
    // the default of all districts and add it to the Select
    var textElement	= document.getElementById("chooseDistText");
    addOption(distSelect, textElement.innerHTML, "?");

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
    if ('district' in args)
		setDist(args['district']);
}		// function gotDistFile

/************************************************************************
 *  function setDist													*
 *																		*
 *  This method ensures that the District selection matches				*
 *  a particular value.													*
 *																		*
 *  Input:																*
 *		newDistCode		new district identifier							*
 *																		*
 *  Returns:															*
 *		null if there is no current selected district					*
 *		the former selected district									*
 ************************************************************************/
function setDist(newDistCode)
{
    var	distSelect	= document.distForm.District;
    var	distOpts	= distSelect.options;
    if (distOpts.length == 0)
		return null;
    var	oldValue	= null;
    if (distSelect.selectedIndex >= 0  &&
		distSelect.selectedIndex < distOpts.length)
		oldValue	= distOpts[distSelect.selectedIndex];

    if (newDistCode === undefined || newDistCode === null)
		return oldValue;

    for(var i = 0; i < distOpts.length; i++)
    {
		if (distOpts[i].value == newDistCode)
		{	// found matching entry
		    distSelect.selectedIndex	= i;
		    changeDist();	
		    break;
		}	// found matching entry
    }	// search for district to select
    return oldValue;
}		// function setDist

/************************************************************************
 *  function noDistFile													*
 *																		*
 *  This method is called if there is no census summary file.			*
 *  The selection list of districts is cleared and an error message		*
 *  displayed.															*
 ************************************************************************/
function noDistFile()
{
    var	distSelect	= document.distForm.District;
    distSelect.options.length	= 0;	// clear the selection
    var	censusId	= document.distForm.Census.value;
    var	alertTxtElt	= document.getElementById('noDistFileMsg');
    var	alertTxt	= alertTxtElt.innerHTML.replace('$census',
									censusId);
    popupAlert(alertTxt,
		       distSelect);
}		// function noDistFile

/************************************************************************
 *  function changeDist													*
 *																		*
 *  This method is called when the user selects a new district.			*
 *																		*
 *  Input:																*
 *		this			<select name='District'>						*
 *		ev              Javascript change Event                         *
 ************************************************************************/
function changeDist(ev)
{
    // identify the selected district
    var	distSelect	= document.distForm.District;
    var	optIndex	= distSelect.selectedIndex;
    if (optIndex == -1)
		optIndex	= 0;		// default to first entry
    var	optVal	= distSelect.options[optIndex].value;

}		// function changeDist

/************************************************************************
 *  function showForm													*
 *																		*
 *  Show the form for editting the sub-district table.					*
 *  This is invoked by double-clicking on a district in the selection	*
 *  list.																*
 *																		*
 *  Input:																*
 *		this			<select name='District'>						*
 *		ev              Javascript click Event                          *
 ************************************************************************/
function showForm(ev)
{
    document.distForm.submit();
}		// function showForm
