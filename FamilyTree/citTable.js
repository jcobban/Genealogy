/************************************************************************
 *  citTable.js															*
 *																		*
 *  Javascript code to manage the citations of a particular event		*
 *  using the presentation layout in citTable.inc						*
 *																		*
 *  History:															*
 *		2010/10/16		created											*
 *		2010/10/17		prevent double invocation						*
 *		2010/10/22		fix delete row 0								*
 *		2010/11/11		disable adding citations until finished			*
 *		2010/11/28		change name of cookie for retaining last		*
 *						citation										*
 *		2010/12/04		correct cookie name to be consistent both when	*
 *						created and when referenced.					*
 *		2011/01/18		improve error diagnostics						*
 *		2011/01/28		check values of type and idime before adding	*
 *						citation										*
 *		2011/01/31		remove "Create New Source" item from selection	*
 *		2011/02/27		simplify initialization							*
 *						add callback method updateCitation to permit	*
 *						editCitation.php script to update the displayed	*
 *						list of citations								*
 *		2011/04/22		fixups for IE7									*
 *		2011/09/18		additional fixups for IE7						*
 *		2012/01/13		change class names								*
 *		2012/03/07		popup "loading..." indicator					*
 *						use createFromTemplate instead of explicit DOM	*
 *						creates											*
 *		2013/05/15		add ability to create new source for citation	*
 *		2014/02/19		feedback routine renamed updateCitation			*
 *						simplify locating elements to update in			*
 *						function updateCitation and remove dependency	*
 *						on table implementation							*
 *		2014/03/06		increase size of edit citation dialog window	*
 *		2014/04/15		Display default citation while waiting for		*
 *						database server to respond to request for list	*
 *						of sources										*
 *						record name of source in cookie					*
 *		2014/10/01		request user to confirm delete of citation		*
 *						use global debug flag from util.js				*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/06/01		open child dialogs in other half of window		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  dialogDiv															*
 *																		*
 *  global variable to hold a reference to a displayed dialog			*
 ************************************************************************/
var dialogDiv		= null;

/************************************************************************
 *  citChildFrameClass													*
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var citChildFrameClass	= 'left';

/************************************************************************
 *  commonConfig														*
 *																		*
 *  common part of window configuration parameters						*
 ************************************************************************/
var commonConfig	= ",status=yes,resizable=yes,scrollbars=yes";

/************************************************************************
 *  initCit																*
 *																		*
 *  Initialize element for citation table support.						*
 *  The onload method of a page that includes citTable.inc must call	*
 *  this method.														*
 *  This method activates the dynamic functionality of buttons within	*
 *  the citation table.													*
 *																		*
 ************************************************************************/
function initCit()
{
    //alert("citTable.js: initCit()");

    // determine which half of the window child frames are opened
    if (window.frameElement)
    {				// dialog opened in half frame
		citChildFrameClass		= window.frameElement.className;
		if (citChildFrameClass == 'left')
		    citChildFrameClass	= 'right';
		else
		    citChildFrameClass	= 'left';
    }				// dialog opened in half frame
    try {
		var	citTable	= document.getElementById('citTable');
		// define feedback function from editCitation.php
		citTable.updateCitation	= updateCitation;

		// locate the enclosing form
		var form	= citTable.parentNode;
		while(form.nodeName != 'FORM')
		    form	= form.parentNode;

		// define onclick handlers for some elements
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{		// loop through elements
		    var elt	= formElts[i];
		    if (elt.id.substr(0,"editCitation".length) == "editCitation")
				elt.onclick	= editCitation;
		    else
		    if (elt.id.substr(0,"delCitation".length) == "delCitation")
				elt.onclick	= deleteCitation;
		    else
		    if (elt.id == "addCitation")
				elt.onclick	= addCitation;
		}		// loop through elements
    } catch (e) {
		alert("citTable.js: initCit: error " + e.message);
    }		// catch
}		// initCit

/************************************************************************
 *  addCitation															*
 *																		*
 *  This method is called when the user requests to add 				*
 *  a citation to the event.											*
 *																		*
 *  Input:																*
 *		this			<button id='addCitation'> element				*
 ************************************************************************/
function addCitation()
{
    this.disabled	= true;			// prevent double add cit
    var	form		= this.form;
    var	cell		= this.parentNode;
    var row		= cell.parentNode;
    var sect		= row.parentNode;
    var	table		= sect.parentNode;

    // identification of the event to be cited
    var type		= form.citType.value;	// event type
    var idime		= form.idime.value;	// key of associated record
    if (type < 1)
    {
		alert("addCitation: invalid value of type=" + type);
		return;
    }
    if (idime < 1)
    {
		alert("addCitation: invalid value of idime=" + idime);
		return;
    }

    // add a new row before the current row
    var	rowNum	    	= row.rowIndex - 1;
    var	cookie	    	= new Cookie("familyTree");
    var	detail	    	= '';
    var	idsr	    	= 0;
    var	sourceName  	= '';
    if (cookie.text)
    {			// remember last value entered
		detail		    = cookie.text;
    }			// remember last value entered
    if (cookie.idsr)
    {			// recall last value entered
		idsr		    = cookie.idsr;
    }			// recall last value entered
    if (cookie.sourceName)
    {			// recall last value entered
		sourceName	    = cookie.sourceName;
    }			// recall last value entered
    else
    {			// backwards compatibility
		sourceName	    = 'Source for IDSR=' + idsr;
    }			// backwards compatibility

    var	parms		    = {"rownum"	:       rowNum,
				    		"detail" :      detail,
				    		'idsr' :        idsr,
				    		'sourceName' :  sourceName};
    var	template	    = document.getElementById('sourceRow$rownum');
    var newRow		    = createFromTemplate(template,
					    			     parms,
					    			     null);
    table.tBodies[0].appendChild(newRow);

    // set actions for detail input text field
    var detailTxt	    = form.elements["Page" + rowNum];
    detailTxt.onblur	= createCitation;
    detailTxt.onchange	= createCitation;

    // populate the select with the list of defined sources to 
    // in the second cell.  The name of the <select> element,
    // the numeric key of the <option> to select, and the name of
    // the <form> are passed as parameters so they can be returned
    // in the response.
    var sourceCell	= form.elements["Source" + rowNum];
    popupLoading(sourceCell);	// display loading indicator
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=Source' + rowNum +
						"&idsr=" + cookie.idsr +
						"&formname=" + form.name,
				gotSources,
				noSources);
}		// addCitation

/************************************************************************
 *  gotSources															*
 *																		*
 *  This method is called when the XML file representing				*
 *  the list of sources from the database is retrieved.					*
 *																		*
 *  Parameters:															*
 *		xmlDoc		information about the defined sources as an         *
 *		            XML document                                        *
 ************************************************************************/
function gotSources(xmlDoc)
{
    // get the name of the select element to be updated from the XML document
    var nameElts	= xmlDoc.getElementsByTagName('name');
    var	name		= '';
    try {
    if (nameElts.length >= 1)
    {		// name returned
		name	= nameElts[0].textContent;
    }		// name returned
    else
    {		// name not returned
		alert("citTable.js: gotSources: " +
				"name value not returned from getSourcesXml.php");
		return;
    }		// name not returned
    }
    catch(e) {
		alert("citTable.js: gotSources: nameElts=" + nameElts);
    }

    // get the idsr of the select option to be highlighted
    var idsrElts	= xmlDoc.getElementsByTagName('idsr');
    var	idsr		= null;
    if (idsrElts.length >= 1)
    {		// idsr returned
		idsr	= idsrElts[0].textContent;
    }		// idsr returned

    // get the formname of the select option to be highlighted
    var formnameElts	= xmlDoc.getElementsByTagName('formname');
    var	formname	= null;
    if (formnameElts.length >= 1)
    {		// formname returned
		formname	= formnameElts[0].textContent;
    }		// formname returned
    else
    {		// name not returned
		alert("citTable.js: gotSources: formname value not returned from getSourcesXml.php");
		return;
    }		// name not returned

    // the form element in the web page
    var	form		= document.forms[formname];

    // get the list of sources from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("source");

    // locate the selection element in the web page to be updated
    var	sourceSelect	= form.elements[name];
    if (sourceSelect == null)
    {
		var msg	= "";
		for(var i = 0; i < form.elements.length; i++)
		{
		    msg += form.elements[i].name + ", ";
		    if (form.elements[i].name == name)
		    {
				sourceSelect	= form.elements[i];
				break;
		    }
		}
		if (sourceSelect == null)
		{		// sourceSelect still null
		    alert("citTable.js: gotSources: could not find named element '" +
				name + "', element names=" + msg);
		    return;
		}		// sourceSelect still null
    }

    // purge old options on the select if any
    if (sourceSelect.options)
		sourceSelect.options.length	= 0;	// purge old options if any
    else
		alert("citTable.js: gotSources:" + tagToString(sourceSelect));

    hideLoading();	// hide loading indicator
    // create a new HTML Option object to represent the ability to
    // create a new source and add it to the Select as the first option
    option	= addOption(sourceSelect,	// Select element
						    'Add New Source',	// text value to display
						    -1);	// key to request add
    sourceSelect.onchange	= checkForAdd;

    // customize selection
    sourceSelect.size		= 10;	// height of selection list

    // add the options from the XML file to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {		// loop through source nodes
		var	node		= newOptions[i];

		// get the text value to display to the user
		// this is the name of the source
		var	text		= node.textContent;

		// get the "id" attribute, this is the IDSR value identifying
		// the source.  It becomes the value of the Option. 
		var	value		= node.getAttribute("id");
		if ((value == null) || (value.length == 0))
		{		// cover our ass
		    value		= text;
		}		// cover our ass

		// create a new HTML Option object and add it to the Select
		option	= addOption(sourceSelect,	// Select element
						    text,	// text value to display
						    value);	// unique key of source record

		// select the last source chosen by the user
		if (idsr &&
		    (value == idsr))
				option.selected	= true;

    }		// loop through source nodes

    sourceSelect.focus();		// give selection list the focus

}		// gotSources

/************************************************************************
 *  noSources															*
 *																		*
 *  This method is called if there is no sources file on the server.	*
 ************************************************************************/
function noSources()
{
    alert("citTable.js: noSources error");
}		// noSources

/************************************************************************
 *  createCitation														*
 *																		*
 *  The user has requested to add a citation and supplied all of		*
 *  the required information.											*
 *																		*
 *  Parameters:															*
 *		this			the input element for which this is the			*
 *						onchange or onblur method						*
 ************************************************************************/
function createCitation()
{
    // prevent double invocation
    this.onchange		= null;
    this.onblur			= null;

    // get parameters from the form containing this cell
    var	form			= this.form;		// form containing element
    document.getElementById('AddCitation').disabled
					    = false;	        // re-enable adding citations
    var formname		= form.name;		// name of the form
    var idime			= form.idime.value;	// key of associated record
    var type			= form.citType.value;	// type of event within record
    var pageText		= this.value;		// value of page element
    var	cell			= this.parentNode;	// cell containing page element
    var row			    = cell.parentNode;	// row containing page element
    var rownum			= row.rowIndex - 1;	// position within table
    var cell2			= row.cells[1];		// 2nd cell in same row
    var sourceSel		= form.elements["Source" + rownum];
    var sourceOpt		= null;
try {
    sourceOpt		    = sourceSel.options[sourceSel.selectedIndex];
    }
    catch(e) {
		var	names	= "";
		for(var n in form)
		    names	+= n + ", ";
		alert("createCitation: form.elements[" + names + "], name='Source" +
                        rownum + "', sourceSel=" + sourceSel);
    }
    var idsr		= sourceOpt.value;

    if (idsr < 1)
    {			// create a new source
		form.sourceCreated	= sourceCreated;
		openFrame("source",
				  "editSource.php?idsr=0&form=" + formname +
								"&select=" + sourceSel.name,
				  citChildFrameClass);
		return;
    }			// create a new source

    // update the cookies for the IDSR value of the last source requested
    // and the citation text
    var	cookie		    = new Cookie("familyTree");
    cookie.idsr		    = idsr;
    cookie.text		    = pageText;
    cookie.sourceName	= sourceOpt.innerHTML;
    cookie.store(10);		// keep for 10 days

    // parameters passed by method='post'
    var parms	    	= {
				    		"idime" 	: idime,
				    		"type"		: type,
				    		"idsr"		: idsr,
				    		"page"		: pageText,
				    		"row"		: rownum,
				    		"formname"	: formname,
                            "debug"     : debug}; 

	var msg	            = "parms={";
	var comma	        = '';
	for(var pname in parms)
	{
	    msg	            += comma + pname + "='" + parms[pname] + "'";
        comma           = ',';
	}
	msg		            += "}";

    console.log("citTable.js: createCitation: 425 " + msg);

    if (debug != 'n')
    {			// debugging activated
		alert("citTable.js: createCitation: " + msg );
    }			// debugging activated

    // send the request to add a citation to the server requesting
    // an XML response
    HTTP.post('/FamilyTree/addCitXml.php',
		      parms,
		      gotAddCit,
		      noAddCit);
}	// createCitation

/************************************************************************
 *  gotAddCit															*
 *																		*
 *  This method is called when the XML file representing				*
 *  the addition of a citation is retrieved.							*
 *																		*
 *  Parameters:															*
 *		xmlDoc		information about the added citation				*
 ************************************************************************/
function gotAddCit(xmlDoc)
{
    var	xmlRoot		= xmlDoc.documentElement;
    if (xmlRoot)
    {
		if (xmlRoot.nodeName == "addCit")
		{		// valid response
		    // alert("citTable.js: gotAddCit: " + tagToString(xmlRoot));
		    var	rowNum		= xmlRoot.getAttribute("row");
		    var	formname	= xmlRoot.getAttribute("formname");
		    var	form		= document.forms[formname];
		    var	sourceName	= "";
		    var	sourceList	= xmlRoot.getElementsByTagName("source");
		    if (sourceList.length > 0)
				sourceName	= sourceList[0].textContent;

		    var	idsx		= "";
		    var	idsxList	= xmlRoot.getElementsByTagName("idsx");
		    if (idsxList.length > 0)
				idsx		= idsxList[0].textContent;

		    // locate elements in web page to be updated
		    var	trow	= document.getElementById('sourceRow' + rowNum);
		    var	table	= trow.parentNode;
		    if (trow == null)
		    {		// unexpected getElementById failed
				var	rowinfo	= "";
				for(var i = 0; i < table.rows.length; i++)
				{
				    var tr	= table.rows[i];
				    rowinfo	+= "<tr id='" + tr.id + "'> ";
				    if (tr.id == ('sourceRow' + rowNum))
				    {
						rowinfo	+= " matched ";
						trow	= tr;
				    }
				}
		    }		// unexpected getElementById failed

		    var	td1	= trow.cells[0];
		    var	td2	= trow.cells[1];
		    var	td3	= trow.cells[2];
		    var	td4	= trow.cells[3];
		    //var	page	= form.elements["Page" + rowNum].value;

		    var	parms	= {"rownum"	: rowNum,
						       "idsx"	: idsx,
						       "title"	: sourceName};

		    // the first cell of the table was created empty
		    // we now hide the IDSX value in the cell and add
		    // an "Edit Citation" button;
		    while(td1.firstChild != null)
				td1.removeChild(td1.firstChild);
		    var	b1	= document.getElementById('firstButtonTemplate');
		    for(ie = 0; ie < b1.childNodes.length; ie++)
		    {
				td1.appendChild(createFromTemplate(b1.childNodes[ie],
									   parms,
									   null));
		    }
		    var editButton		= form.elements["editCitation" + rowNum];
		    editButton.onclick	= editCitation;

		    // the second cell in the row contained the <select> element
		    // from which the user chose the master source for this citation
		    // this is deleted and replaced by a read-only text element
		    // containing the name of the master source
		    while(td2.firstChild != null)
				td2.removeChild(td2.firstChild);
		    var	b2	= document.getElementById('sourceTextTemplate');
		    for(ie = 0; ie < b2.childNodes.length; ie++)
		    {
				td2.appendChild(createFromTemplate(b2.childNodes[ie],
									   parms,
									   null));
		    }

		    // the 3rd cell contains the <input type='text'> element containing
		    // the citation detail (page number) text.  This cell is made
		    // read-only
		    var tdPage		= form.elements["Page" + rowNum];
		    tdPage.readOnly	= true;

		    // the 4th cell was initially empty
		    // add a button to delete this citation
		    while(td4.firstChild != null)
				td4.removeChild(td4.firstChild);
		    var	b4	= document.getElementById('secondButtonTemplate');
		    for(ie = 0; ie < b4.childNodes.length; ie++)
		    {
				td4.appendChild(createFromTemplate(b4.childNodes[ie],
									   parms,
									   null));
		    }
		    var delButton	= form.elements["delCitation" + rowNum];
		    delButton.onclick	= deleteCitation;
		}		// valid response
		else	// unexpected response
		    alert("citTable.js: gotAddCit: " +
				  "xmlRoot='" + tagToString(xmlRoot) + "'");
    }
    else
		alert("citTable.js: gotAddCit: xmlDoc='" + xmlDoc + "'");
}		// gotAddCit

/************************************************************************
 *  noAddCit															*
 *																		*
 *  This method is called if there is no add citation response			*
 *  from the server.													*
 ************************************************************************/
function noAddCit()
{
    alert("citTable.js: noAddCit: script addCitXml.php not found on server");
}		// noAddCit

/************************************************************************
 *  editCitation														*
 *																		*
 *  This method is called when the user requests to edit				*
 *  a citation to a source for an event.  The script editCitation.php	*
 *  is invoked in a dialog window.										*
 *																		*
 *  Input:																*
 *		this		<button id='editCitation999'>						*
 ************************************************************************/
function editCitation()
{
    var	form		= this.form;
    var	idsx		= this.id.substr("editCitation".length);
    openFrame("citation",
		      "editCitation.php?idsx=" + idsx, 
		      citChildFrameClass);
}	// editCitation

/************************************************************************
 *  deleteCitation														*
 *																		*
 *  This method is called when the user requests to delete				*
 *  a citation to a source for an event.								*
 *																		*
 *  Input:																*
 *		this		<button id='delCitation999'>						*
 ************************************************************************/
function deleteCitation()
{
    var	form		= this.form;
    var	idsx		= this.id.substr("delCitation".length);

    var parms		= {"idsx"	: idsx,
						   "rownum"	: idsx,
						   "formname"	: form.name, 
						   "template"	: "",
						   "msg"	:
						"Are you sure you want to delete this citation?"};

    if (debug != 'n')
		parms["debug"]	= debug;

    // ask user to confirm delete
    dialogDiv	= document.getElementById('msgDiv');
    if (dialogDiv)
    {		// have popup <div> to display message in
		displayDialog(dialogDiv,
				      'CitDel$template',
				      parms,
				      this,		// position relative to
				      confirmDelete,	// 1st button confirms Delete
				      false);		// default show on open
    }		// have popup <div> to display message in
    else
		alert("editEvent.js: deleteCitation: Error: " + msg);
}		// deleteCitation

/************************************************************************
 *  confirmDelete														*
 *																		*
 *  This method is called when the user confirms the request to delete	*
 *  a citation to a source for an event.								*
 *																		*
 *  Input:																*
 *		this		<button id='confirmDelete...'>						*
 ************************************************************************/
function confirmDelete()
{
    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	idsx		= this.id.substr(13);
    var	rownum		= form.elements['rownum' + idsx].value;
    var	formname	= form.elements['formname' + idsx].value;

    var parms		= {"idsx"	: idsx.toString(),
						   "rownum"	: rownum.toString(),
						   "formname"	: form.name}; 

    if (debug != 'n')
		parms["debug"]	= debug;

    // hide the dialog
    dialogDiv.style.display	= 'none';

    // invoke script to update Event and return XML result
    HTTP.post('/FamilyTree/deleteCitationXml.php',
		      parms,
		      gotDeleteCit,
		      noDeleteCit);
}	// deleteCitation

/************************************************************************
 *  gotDeleteCit														*
 *																		*
 *  This method is called when the XML file representing				*
 *  a deleted citation is retrieved from the database.					*
 *																		*
 *  Parameters:															*
 *		xmlDoc		information about the deleted citation				*
 ************************************************************************/
function gotDeleteCit(xmlDoc)
{
    var	root		= xmlDoc.documentElement;
    if (root && (root.nodeName == 'deleted'))
    {		// valid XML response
		var	rownum		= root.getAttribute("rownum");
		var	idsx		= root.getAttribute("idsx");
		var	formname	= root.getAttribute("formname");
		var	form		= document.forms[formname];
		for (var i = 0; i < root.childNodes.length; i++)
		{		// loop through immediate children of root
		    var	elt	= root.childNodes[i];
		    if (elt.nodeType == 1)
		    {		// only examine elements at this level
				if (elt.nodeName == 'msg')
				{	// error message
				    alert(elt.textContent);
				    return;	// do not perform any other functions
				}	// error message
		    }		// only examine elements at this level
		}		// loop through immediate children of root
		var	row	= document.getElementById("sourceRow" + rownum);
		var	sect	= row.parentNode;
		if (row)
		    sect.removeChild(row);
    }		// valid XML response
    else
    {		// error unexpected document
		if (root)
		    msg	= tagToString(root);
		else
		    msg	= xmlDoc;
		alert ("citTable.js: gotDeleteCit: Error: " + msg);
    }		// error unexpected document
}		// gotDeleteCit

/************************************************************************
 *  noDeleteCit															*
 *																		*
 *  This method is called if there is no delete citation response		*
 *  from the server.													*
 ************************************************************************/
function noDeleteCit()
{
    alert("citTable.js: noDeleteCit()");
}		// noDeleteCit

/************************************************************************
 *  updateCitation														*
 *																		*
 *  This method is called by the editCitation.php script to feed back	*
 *  the results so they can be reflected in this page.					*
 *																		*
 *  Parameters:															*
 *		this			instance of HtmlTable for id='citTable'			*
 *		idsx			unique numeric key of instance of Citation		*
 *		idsr			unique numeric key of instance of Source		*
 *		sourceName		textual name of source for display				*
 *		page			source detail text (page number)				*
 ************************************************************************/
function updateCitation(idsx,
						idsr,
						sourceName,
						page)
{
    try {
		document.getElementById("Source" + idsx).value	= sourceName;
		document.getElementById("Page" + idsx).value	= page;
    } catch(e) {
		alert("citTable.js: updateCitation: error " + e.message);
    }
}		// updateCitation

/************************************************************************
 *  checkForAdd															*
 *																		*
 *  The user has selected a different option in the selection list of	*
 *  sources.															*
 *																		*
 *  Parameters:															*
 *		this			the <select> element for which this is the		*
 *						onchange method									*
 ************************************************************************/
function checkForAdd()
{
    var	option	= this.options[this.selectedIndex];
    if (option.value < 1)
    {		// create new source
		var	formName	= this.form.name;
		var	elementName	= this.name;
		openFrame("source",
				  "editSource.php?idsr=0&form=" + formName +
								"&select=" + elementName,
				  citChildFrameClass);
    }		// create new source
}		// checkForAdd

/************************************************************************
 *  sourceCreated														*
 *																		*
 *  This method is called when a child window notifies this script		*
 *  that a new source has been created.									*
 *  The new source is added to the end of the selection list, out of	*
 *  alphabetical order, and made the currently selected item.			*
 *																		*
 *  Input:																*
 *		this			<form ...>										*
 *		parms			associative array of field values				*
 *						parms.elementname		= name of <select>		*
 ************************************************************************/
function sourceCreated(parms)
{
    alert("citTable.js:sourceCreated:");
    var	form		= this;
    var	formName	= form.name;
    var	element		= form.elements[parms.elementname];
    if (element)
    {		// element found in caller
		// update the selection list in the invoking page
		var	option	= addOption(element,
							    parms.srcname,
							    parms.idsr);
		element.selectedIndex	= option.index;
    }		// element found in caller
    else
    {		// element not found in caller
		alert("editEvent.js: sourceCreated: <select name='" +
				parms.elementname +
				"'> not found in <form name='" + formName +
				"'> in calling page");
    }		// element not found in caller

    return false;
}	// sourceCreated


