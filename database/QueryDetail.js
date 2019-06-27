/************************************************************************
 *  QueryDetail.js														*
 *																		*
 *  Common dynamic functionality of census query pages					*
 *  QueryDetailYYYY.html.												*
 *																		*
 *  History:															*
 *		2011/04/10		split off from util.js							*
 *						use a cookie to remember last district used for *
 *						each census.									*
 *		2011/09/03		support a comma-separated list of				*
 *						district:subdistrict pairs in the option value	*
 *						for a sub-district selection This is to support	*
 *						collapsing the 1911 selection lists				*
 *		2011/10/07		clear the sub-district selection list if the	*
 *						user specifies to search all districts			*
 *						make coding more consistent and add error		*
 *						function handling								*
 *		2011/12/28		popup a loading indicator while waiting for a	*
 *						server response									*
 *						pop up help balloon if mouse held over field	*
 *		2012/01/20		use id= rather than name= for buttons to avoid	*
 *						problem with IE									*
 *		2012/01/24		use popupLoading and hideLoading methods		*
 *		2012/04/01		use getElementById instead of getEltId			*
 *						support for 1911 and 1916 broke support for		*
 *						older censuses									*
 *						if the page number is set and there is an		*
 *						"OrderBy" selection list, set it to "Line"		*
 *		2012/09/27		correct problems arising from confusion between	*
 *						census identifier and census year by using the	*
 *						name censusId for the identifier and censusYear	*
 *						for the year.									*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/12/13		form layout done using CSS instead of tables	*
 *		2014/08/10		comments updated.  No functional changes.		*
 *		2014/09/12		remove use of obsolete selectOptByValue			*
 *		2014/10/14		indices of args array are now lower case		*
 *		2017/09/19		support lang=fr									*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/19      call element.click to trigger button click      *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoadDetail;

/************************************************************************
 *  function onLoadDetail												*
 *																		*
 *  The onload method of a QueryDetailYyyy.html web page.  				*
 *  This is invoked after the web page has been loaded into the browser.*
 *  Obtain the list of districts in the census as an XML file				*
 *  from the web server.												*
 *																		*
 *  Input:																*
 *		this		window object												*
 ************************************************************************/
function onLoadDetail()
{
    document.body.onkeydown		= qdKeyDown;

    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		// get the census identifier from the invoking form
		// This has the format "CCYYYY" where "CC" is the administrative
		// domain, usually the country code, and "YYYY" is the census year 
		var	censusId	= form.Census.value;
		var	censusYear	= censusId.substring(2);


		// initialize dynamic functionality of form elements
		var	elements	= form.elements;
		var	listofnames	= "";
		for (var i = 0; i < elements.length; i++)
		{	// loop through all form elements
		    var	element		= elements[i];
		    element.onkeydown	= keyDown;

		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;
		    listofnames	+= name + ", ";

		    switch(name)
		    {	// act on specific elements by name
				case 'Coverage':
				{
				    element.onclick	= showCoverage;
				    break;
				}	// display status of transcription

				case 'Province':
				{
				    element.onchange	= changeProv;
				    break;
				}	// user selected different province

				case 'Page':
				{
				    element.onchange	= changePage;
				    break;
				}	// user selected different page

				case 'Surname':
				{
				    element.focus();	// initial keyboard focus
				    break;
				}	// surname field

				case 'District[]':
				{
				    // display an indicator that we are waiting for
				    // response from the server
				    popupLoading(element);

				    // get the district information file
				    var	distUrl	= "CensusGetDistricts.php?";
				    if (censusYear < 1867)
				    {		// pre-confederation census by province
						var	provSelect	= form.Province;
						var	optIndex	= provSelect.selectedIndex;
						if (optIndex != -1)
						{		// province selected
						    var	prov	= provSelect.options[optIndex].value;
						    distUrl	+= "Census=" + prov + censusYear;
						}		// province selected
						else
						    distUrl	+= "Census=" + censusId;
				    }		// pre-confederation census by province
				    else
					    distUrl	+= "Census=" + censusId;
				    if ('lang' in args)
					    distUrl	+= '&lang=' + args['lang'];
				    HTTP.getXML(distUrl,
						        gotDistFile,
						        noDistFile);
				    break;
				}	// districts selection
		    }	// act on specific elements by name
		}	// loop through all form elements
		//alert("QueryDetail.js: onLoadDetail: listofnames=" + listofnames);
    }		// web page has a form named distForm
    else
		alert("QueryDetail.js: onLoad: invoking web page does not contain " +
				"a <form name='distForm'>");

    // initialize dynamic functionality of labels
    elements	= document.getElementsByTagName("label");
    for (var i = 0; i < elements.length; i++)
    {		// loop through all labels
		var	element		= elements[i];
		// pop up help balloon if the mouse hovers over a label
		// for more than 2 seconds
		element.onmouseover	= eltMouseOver;
		element.onmouseout	= eltMouseOut;
    }		// loop through all labels

}		// onLoadDetail

/************************************************************************
 *  function gotDistFile														*
 *																		*
 *  This method is called when the XML file, listing all the districts 		*
 *  in the province, has been retrieved from the web server.				*
 *																		*
 *  Input:																*
 *		xmlDoc				Document representing the XML file				*
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    if (xmlDoc === null)
		return noDistFile();

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		// get the census identifier from the invoking form
		// This has the format "CCYYYY" where "CC" is the administrative
		// domain, usually the country code, and "YYYY" is the census year 
		var	censusId	= form.Census.value;
		var	censusYear	= censusId.substring(2);
		if (censusYear < 1867)	// pre-confederation
		    censusId		= form.Province.value + censusYear;

		// if the district selection list supports multiple selection
		// then the name of the element has array subscript brackets
		// appended by PHP convention.
		var	distSelect		= form.elements["District[]"];
		if (distSelect === undefined)
		    distSelect			= form.District;
		distSelect.options.length	= 0;	// clear the selection
		distSelect.onchange		= changeDist;

		// create a new HTML Option object and add it as the first
		// option to the Select.  This ensures that when the user selects
		// any other option that the onchange method is called
		var	opt		= addOption(distSelect,
							    "All Districts",
							    "");
		opt.selected	= true;

		// get the list of districts from the XML file
		var newOptions		= xmlDoc.getElementsByTagName("option");

		// add the districts to the Select
		for (var i = 0; i < newOptions.length; ++i)
		{
		    // get the source "option" node
		    // note that although this has the same contents and attributes
		    // as an HTML "option" statement, it is an XML Element object,
		    // not an HTML Option object.
		    var	node	= newOptions[i];

		    // get the "value" attribute, the district number
		    var	value	= node.getAttribute("value");

		    // get the text, district name, to display to the user
		    var text	= node.textContent;
		    if ((value == null) || (value.length == 0))
		    {		// cover our ass
				value	= text;
		    }		// cover our ass
		    else
		    {		// append district number to displayed text
				text	+= " [dist=" + value + "]";
		    }		// append district number to displayed text

		    // create a new HTML Option object and add it to the Select
		    var	option	= addOption(distSelect,
							text,
							value);
		}			// loop through source "option" nodes

		// if implied by the environment, set the initial selection
		var	district	= args["district"];
		if (!district)
		{		// initial district not explicitly set
		    // get the initially selected district number from a cookie
		    var	cookie		= new Cookie("familyTree");
		    district		= cookie[censusId];
		    if (district === undefined || district === null)
				district	= "";
		}

		// select the option whose value matches the supplied district number
		distSelect.value	= district;

		// take action for change in selection
		// this populates the subdistrict selection list
		if (distSelect.onchange)
		    distSelect.onchange();
    }		// web page has a form named distForm
}		// gotDistFile

/************************************************************************
 *  function noDistFile														*
 *																		*
 *  This method is called if there is no list of districts for this		*
 *  census.  The selection list of districts is cleared.				*
 ************************************************************************/
function noDistFile()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		// get the census identifier from the invoking form
		// This has the format "CCYYYY" where "CC" is the administrative
		// domain, usually the country code, and "YYYY" is the census year 
		var	censusId	= form.Census.value;
		var	censusYear	= censusId.substring(2);
		if (censusYear < 1867)	// pre-confederation
		    censusId		= form.Province.value + censusYear;

		// if the district selection list supports multiple selection
		// then the name of the element has array subscript brackets
		// appended by PHP convention.
		var	distSelect		= form.elements['District[]'];
		if (distSelect === undefined)
		    distSelect			= form.District;
		distSelect.options.length	= 0;	// clear the selection
		addOption(distSelect,
				  "Census summary file \"CensusGetDistricts.php?Census=" +
					censusId +
					"\" unavailable",
					"");
    }		// web page has a form named distForm
}		// noDistFile

/************************************************************************
 *  function changeProv														*
 *																		*
 *  The onchange method of the Province select element.						*
 *  Take action when the user selects a new province.						*
 *																		*
 *  Input:																*
 *		this		<select name='Province'>								*
 ************************************************************************/
function changeProv()
{
    var	provSelect	= this;
    var	optIndex	= provSelect.selectedIndex;
    if (optIndex == -1)
		return;	// nothing selected

    // get the two character province code
    var	optVal	= provSelect.options[optIndex].value;

    // take action on the selected province
    onLoadProv(optVal);
}		// changeProv

/************************************************************************
 *  function changePage														*
 *																		*
 *  The onchange method of the Page select element.						*
 *  Take action when the user selects a new province.						*
 *																		*
 *  Input:																*
 *		this		<select name='Page'>										*
 ************************************************************************/
function changePage()
{
    if (this.value.length > 0 &&
		(this.value - 0) > 0 &&
		distForm.OrderBy !== undefined)
		document.distForm.OrderBy.selectedIndex	= 1;
}		// changePage


/************************************************************************
 *  function onLoadProv														*
 *																		*
 *  Use Ajax to obtain the list of districts in the census 				*
 *  for a specific province as an XML file from the Web server.				*
 *																		*
 *  Input:																*
 *		prov		two character province code								*
 ************************************************************************/
function onLoadProv(prov)
{
    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		// get the census identifier from the invoking form
		// This has the format "CCYYYY" where "CC" is the administrative
		// domain, usually the country code, and "YYYY" is the census year 
		var	censusId	= form.Census.value;
		var	censusYear	= censusId.substring(2);
		if (censusYear < 1867)	// pre-confederation
		    censusId		= form.Province.value + censusYear;

		// display an indicator that we are waiting for server response
		popupLoading();

		// get the district information from the server
		var distUrl	= "CensusGetDistricts.php";
		if (censusYear < 1867)
		{		// pre-confederation
		    distUrl	+= "?Census=" + prov + censusYear;
		}		// pre-confederation
		else
		{		// post-confederation
		    distUrl	+= "?Census=" + censusId +
					    "&Province=" + prov;
		}		// post-confederation
		if ('lang' in args)
		    distUrl	+= '&lang=' + args['lang'];

		HTTP.getXML(distUrl,
				    gotDistFile,
				    noDistFile);
    }		// web page has a form named distForm
}		// onLoadProv

/************************************************************************
 *  function changeDist														*
 *																		*
 *  This method is called when the user selects a new district.				*
 *  This includes extending or retracting a multiple selection.				*
 *  A request is sent to the server to retrieve the list of				*
 *  subdistricts associated with the currently selected districts.		*
 *																		*
 *  Input:																*
 *		this		<select name='District[]'>								*
 ************************************************************************/
function changeDist()
{
    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		// get the census identifier from the invoking form
		// This has the format "CCYYYY" where "CC" is the administrative
		// domain, usually the country code, and "YYYY" is the census year 
		var censusId		= form.Census.value;
		var	censusYear	= censusId.substring(2);
		if (censusYear < 1867)	// pre-confederation
		    censusId		= form.Province.value + censusYear;

		// identify the selected district
		// if the district selection list supports multiple selection
		// then the name of the element has array subscript brackets
		// appended by PHP convention.
		var distSelect		= form.elements['District[]'];
		if (distSelect === undefined)
		    distSelect		= form.District;

		// accumulate a list of selected district numbers in an array
		var	dists		= new Array();
		var	options		= distSelect.options;
		for (var i = 0; i < options.length; ++i)
		{		// check all options
		    if ((options[i].selected) &&
				(options[i].value.length > 0))
				dists.push(options[i].value);
		}		// check all options
		if (dists.length == 0)
		{		// clear subdistricts
		    var	subdistSelect			= form.SubDistrict;
		    subdistSelect.options.length	= 0;
		    return;
		}		// clear subdistricts

		// save the first or only district number in a cookie
		var	cookie		= new Cookie("familyTree");
		cookie[censusId]	= dists[0];
		cookie.store(10);		// keep for 10 days

		// Create the query that obtains the subdistrict info for
		// the selected districts in an XML response
		var	parms	= new Object();
		parms.Census	= censusId;
		parms.District	= dists;
		// get the subdistrict information file
		HTTP.post("CensusGetSubDistL.php",
				  parms,
				  gotSubDist,
				  noSubDist);
    }		// web page has a form named distForm
}		// changeDist

/************************************************************************
 *  function addSubDistOption												*
 *																		*
 *  This method is called when the sub-district information XML				*
 *  document relating to a particular district is retrieved.				*
 *  The sub-district selection list is updated.								*
 *																		*
 *  Input:																*
 *		name				name of the sub-district						*
 *		optval				option value, comma separated list of				*
 *						dist:subdist pairs								*
 *		node				XML node with details								*
 ************************************************************************/
function addSubDistOption(subdistSelect, name, optval, node)
{
    // set the text value to display to the user
    var text	= name + " [subdist=" + optval + "]";

    // create a new HTML Option object and add it 
    var newOption	= addOption(subdistSelect,
						    text,
						    optval);

    // make the additional information in the XML Option
    // available to the application without changing the
    // appearance of the HTML Option
    newOption.xmlOption	= node;
}		// addSubDistOption

/************************************************************************
 *  function gotSubDist														*
 *																		*
 *  This method is called when the sub-district information XML				*
 *  document relating to a particular district is retrieved.				*
 *  The sub-district selection list is updated.								*
 *																		*
 *  Input:																*
 *		xmlDoc				Document representing the XML file				*
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		var	subdistSelect		= form.SubDistrict;
		subdistSelect.options.length	= 0;	// clear the selection
		// action on changing sub-dist
		subdistSelect.onchange		= changeSubDist;
		// add an instructional entry at the top so onchange is fired
		// if the user chooses any entry but the first
		addOption(subdistSelect,
				  "Choose a Sub-District",
				  "");
    
		if (xmlDoc.documentElement)
		{		// input is a document
		    // get the list of subdistricts to select from
		    var newOptions	= xmlDoc.getElementsByTagName("option");
		
		    var	oldname	= "";	// previous sub-district name
		    var	oldnode	= null;
		    var	optval	= "";	// accumulated list of sub-district identifiers
		    for (var i = 0; i < newOptions.length; ++i)
		    {	// loop through all the subdistrict options
				// get the source "option" node
				// Note that although this has the same contents and attributes
				// as an HTML "option" statement, it is an XML Element
				// object, not an HTML Option object.
				var node	= newOptions[i];
		
				// get the sub-district name to display to the user
				var name	= node.textContent;
		
				// consolidate options with the same name to simplify
				// user access to 1911 and 1916 censuses
				if (name != oldname)
				{		// new township
				    if (oldname != "")
				    {		// add the previous township to the list
					addSubDistOption(subdistSelect,
							 oldname,
							 optval,
							 oldnode);
				    }		// add the previous township to the list
				    // set up for next entry
				    oldname	= name;
				    oldnode	= node;
				    optval	= "";
				}		// new township
		
				// get the sub-district identifier attribute
				var	value	= node.getAttribute("value");
				if ((value == null) || (value.length == 0))
				{		// cover our ass
				    value	= name;
				}		// cover our ass
				// accumulate the list of sub-district identifiers as
				// a comma-separated list
				if (optval.length > 0)
				    optval	+= ",";
				optval		+= value;
		    }			// loop through source "option" nodes
		
		    // add the last sub-district entry to the select statement
		    addSubDistOption(subdistSelect,
					     oldname,
					     optval,
					     oldnode);
		
		    // if implied by the environment, set the initial selection
		    var	District	= args["district"];
		    var	SubDistrict	= args["subdistrict"];
		    if (SubDistrict)
		    {		// set the initial subdistrict
				if ((subdistSelect.options.length > 1) &&
				    (subdistSelect.options[1].value.indexOf(":") > 0))
				{		// option format <dist>:<subdist>
				    subdistSelect.value	= District + ":" + SubDistrict;
				}		// option format <dist>:<subdist>
				else
				{		// option format <subdist>
				    subdistSelect.value	= SubDistrict;
				}		// option format <subdist>
		
				// take action for change in selection
				if (subdistSelect.onchange)
				    subdistSelect.onchange();
		    }		// set the initial subdistrict
		}		// input is a document
		else
		    alert("QueryDetail.js: gotSubDist: xmlDoc=" + xmlDoc);
    }		// web page has a form named distForm
}		// gotSubDist

/************************************************************************
 *  function noSubDist														*
 *																		*
 *  This method is called if there is no sub-district						*
 *  description to return.												*
 *  The selection list of subdistricts is cleared.						*
 ************************************************************************/
function noSubDist()
{
    var	form	= document.distForm;
    if (form)
    {		// web page has a form named distForm
		var	subdistSelect		= form.SubDistrict;
		subdistSelect.options.length	= 0;	// clear the selection
    }		// web page has a form named distForm
}

/************************************************************************
 *  function changeSubDist												*
 *																		*
 *  This method is called when the user selects a new sub-district.		*
 *  If required a selection list of divisions is created in the form.		*
 *  The information for creating the list of divisions was obtained		*
 *  as part of the XML document describing the sub-districts.				*
 *  This is the onchange method of the SubDistrict selection list.		*
 *																		*
 *  Input:																*
 *		this		<select name='SubDistrict'>								*
 ************************************************************************/
function changeSubDist()
{
    var	form		= this.form;
    // identify the selected SubDistrict
    // note that this code only supports a single selection model
    // but a single selection may represent multiple subdistricts
    // in censuses that do not officially support enumeration divisions
    var	subDistSelect	= form.SubDistrict;
    var	optIndex	= subDistSelect.selectedIndex;
    if (optIndex == -1)
    {		// none selected
		return;	// act as if all subdistricts selected
    }		// none selected
    var	subDistOpt	= subDistSelect.options[optIndex];
    var	subDistrict	= subDistOpt.value.split(",");
       
    // if census supports divisions, display a selection list 
    // locate cell to display response in
    var	divSelect	= form.Division;
    
    if (divSelect)
    {		// form supports division selection
		divSelect.options.length= 0;	// clear the selection
		var	subDistXml	= subDistOpt.xmlOption;
		if (subDistrict.length > 1)
		{	// more than one subdistrict in township
		    gotMultSD(subDistrict, divSelect);
		}	// more than one subdistrict in township
		else
		if (subDistXml)
		{	// may be subdivisions in XML
		    gotDivs(subDistXml, divSelect);
		}	// may be subdivisions in XML
		else
		{
		    divSelect.size	= 1;
		}
    }	// census form supports division selection
}		// changeSubDist

/************************************************************************
 *  function gotMultSD														*
 *																		*
 *  This method is called when there is more than one sub-district in		*
 *  a named township.  This is the case for the 1906, 1911, 1916, and		*
 *  1921 censuses of Canada.												*
 *																		*
 *  Input:																*
 *		subDistrict		array of sub-district identifiers				*
 *		divSelect		<select name='Division'>						*
 ************************************************************************/
function gotMultSD(subDistrict,
				   divSelect)
{
    // create a new selection element
    divSelect.size	= 5;
    if (typeof(changeDiv) != 'undefined')
    {		// form has a change handler for the division selection
		divSelect.onchange	= changeDiv;
    }		// form has a change handler for the division selection
    divSelect.onkeydown	= keyDown;	// support advanced editing

    addOption(divSelect,
		      "Choose a Division",
		      "?");

    // add an HTML option for each division defined in the database
    for (var i = 0; i < subDistrict.length; ++i)
    {
		// get the "id" attribute
		var	value	= subDistrict[i];
		var	text	= "division " + value;

		// create a new HTML Option object and add it 
		var	newOption	= addOption(divSelect,
							    text,
							    value);
		newOption.xmlOption	= null;
    }			// loop through source "option" nodes

    // if the environment specifies a choice of division, select it
    var	Division	= args["division"];
    if (Division)
    {		// set the initial division
		divSelect.value	= Division;
    }		// set the initial division

    // take action for change in selection
    // because this does not happen automatically when selectedIndex is set
    if (divSelect.onchange)
		divSelect.onchange();
}		// gotMultSD

/************************************************************************
 *  function gotDivs														*
 *																		*
 *  This method is called when the division information XML document		*
 *  relating to a particular sub-district is retrieved.						*
 *																		*
 *  Input:																*
 *		xmlDoc				Document representing the XML file				*
 *		divSelect		<select name='Division'>						*
 ************************************************************************/
function gotDivs(xmlDoc, divSelect)
{
    // get the list of divisions to select from
    var newOptions		= xmlDoc.getElementsByTagName("div");
   
    if (newOptions.length > 1)
    {			// there are divisions in this subdistrict
		// create a new selection element
		divSelect.size	= 5;
		if (typeof(changeDiv) != 'undefined')
		{		// form has a change handler for the division selection
		    divSelect.onchange	= changeDiv;
		}		// form has a change handler for the division selection
		divSelect.onkeydown	= keyDown;	// support advanced editing
		divSelect.options.length= 0;	// clear the selection

		addOption(divSelect,
				  "Choose a Division",
				  "?");

		// add an HTML option for each division defined in the database
		for (var i = 0; i < newOptions.length; ++i)
		{
		    // get the source "option" node
		    // note that although this has the same contents and appearance
		    // as an HTML "option" statement, it is an XML Element object,
		    // not an HTML Option object.
		    var	node	= newOptions[i];

		    // get the "id" attribute
		    var	value	= node.getAttribute("div");
		    var	text	= "division " + value;

		    // create a new HTML Option object and add it 
		    var	newOption	= addOption(divSelect,
							    text,
							    value);

		    // make any additional information in the XML div node
		    // available to the application without changing the
		    // appearance of the HTML Option
		    newOption.xmlOption	= node;
		}			// loop through source "option" nodes

		// if the environment specifies a choice of division, select it
		var	Division	= args["division"];
		if (Division)
		{		// set the initial division
		    divSelect.value	= Division;
		}		// set the initial division
		else
		// if there is only one division in the sub-district, select it
		if (newOptions.length == 1)
		{
		    divSelect.selectedIndex	= 0;
		}
		else
		{		// no selection
		    divSelect.selectedIndex	= -1;
		}		// no selection

		// take action for change in selection
		// because this does not happen automatically when selectedIndex is set
		if (divSelect.onchange)
		    divSelect.onchange();
    }			// there are divisions in this subdistrict
    else
    {			// empty selection list
		divSelect.size	= 1;
    }			// empty selection list
}		// gotDivs

/************************************************************************
 *  function showCoverage												*
 *																		*
 *  Display a web page showing the transcription coverage of this		*
 *  census.																*
 *																		*
 *  Input:																*
 *		this		<button id='Coverage'>										*
 ************************************************************************/
function showCoverage()
{
    var	form		= document.distForm;
    var	censusId	= form.Census.value;
    location = "CensusUpdateStatus.php?Census=" + censusId;
}		//showCoverage

/************************************************************************
 *  function qdKeyDown														*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.  For		*
 *  example the key combinations Ctrl-S and Alt-Q are interpreted to		*
 *  close the window. update, as shortcut alternatives to using the		*
 *  mouse to click the Quit button										*
 *																		*
 *  Parameters:																*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function qdKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
//  if (code > 32)
//    alert("qdKeyDown: code=" + code + ", e.altKey=" + e.altKey);
    var	form	= document.distForm;

    // take action based upon code
    if (e.ctrlKey)
    {		// ctrl key shortcuts
		if (code == 83)
		{		// letter 'S'
		    form.submit();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }		// ctrl key shortcuts
    
    if (e.altKey)
    {		// alt key shortcuts
		switch (code)
		{
		    case 81:
		    {		// letter 'Q'
				form.submit();
				break;
		    }		// letter 'Q'

		    case 67:
		    {		// letter 'C'
				form.Coverage.click();
				break;
		    }		// letter 'C'

		}	// switch on key code
    }		// alt key shortcuts

    return;
}		// qdKeyDown

