/************************************************************************
 *  editName.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editName.php, which implements the ability to edit				*
 *  details of an name that is recorded in a Name record				*
 *  representing one record in the table tblNX.							*
 *																		*
 *  History:															*
 *		2014/04/08		created											*
 *		2014/04/26		remove sizeToFit								*
 *		2015/02/10		support being invoked in an <iframe>			*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/06/01		permit being invoked in half frame				*
 *						open all child frames in half window			*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2016/02/06		call pageInit on load							*
 *		2016/12/07		activate edit and delete citation buttons		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/19      call element.click to trigger button click      *
 *		2019/08/06      use addEventListener                            *
 *		2020/02/17      hide right column                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

window.onload	= loadEdit;

/************************************************************************
 *  nameChildFrameClass										            *
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var nameChildFrameClass	= 'left';

/************************************************************************
 *  function loadEdit													*
 *																		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function loadEdit()
{
    var	namePattern	= /^([a-zA-Z_]+)(\d+)$/;

    // determine which half of the window child frames are opened
    if (window.frameElement)
    {				// dialog opened in half frame
		nameChildFrameClass	= window.frameElement.className;
		if (nameChildFrameClass == 'left')
		    nameChildFrameClass	= 'right';
		else
		    nameChildFrameClass	= 'left';
    }				// dialog opened in half frame

    // handle keystrokes anywhere in body of page
    document.body.addEventListener('keydown', eeKeyDown);

    // activate functionality of various input fields
    var	focusSet	    = false;
    for (var fi = 0; fi < document.forms.length; fi++)
    {				            // loop through all forms in page
		var form		= document.forms[fi];
		form.updateCitation	= updateCitation;

		// set action methods for form
		if (form.name == 'nameForm')
		{			            // main form
		    form.onsubmit	    = suppressSubmit;
		    form.onreset 	    = resetForm;
		    form.sourceCreated	= sourceCreated;    // feedback function
		}			            // main form

		var formElts	    = form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{			            // loop through all elements in form
		    var element	    = formElts[i];

		    if (element.nodeName.toLowerCase() == 'fieldset')
				continue;

		    var	name;
		    if (element.name && element.name.length > 0)
				name	    = element.name;
		    else
				name	    = element.id;
		    var matches	    = namePattern.exec(name);
		    var	id	        = '';
		    if (matches)
		    {		            // name matched the pattern
				name	    = matches[1];
				id	        = matches[2];   // IDSX of citation
		    }		            // name matched the pattern

		    // take action specific to specific elements
		    switch(name.toLowerCase())
		    {			        // action depends upon element name
				case 'surname':
				{	            // name fields
				    element.addEventListener('keydown', keyDown);
				    element.addEventListener('change', change);	// default
				    element.checkfunc		= checkName;
    			    if (!focusSet)
	    		    {		    // need focus in some field
		    		    element.focus();	// set focus
			    	    focusSet	        = true;
			        }		    // need focus in some field
				    break;
				}	            // name fields

				case 'givenname':
				{	            // name fields
				    element.addEventListener('keydown', keyDown);
				    element.addEventListener('change', change);	// default
				    element.checkfunc		= checkName;
    			    if (!focusSet)
	    		    {		    // need focus in some field
		    		    element.focus();	// set focus
			    	    focusSet	        = true;
			        }		    // need focus in some field
				    break;
				}	            // name fields

				case 'updname':
				{		        // <button id='updName'>
				    element.addEventListener('keydown', keyDown);
				    element.addEventListener('click', updateName);
				    break;
				}		        // <button id='updName'>
				
				case 'clear':
				{		        // <button id='Clear'>
				    element.addEventListener('click', clearNotes);
				    break;
				}		        // <button id='Clear'>
				
				case 'note':
				{		        // textual notes on name
                    var noteEditor      = tinyMCE.get('note');
                    if (noteEditor)
                    {
	    			    if (!focusSet)
		    		    {		    // need focus in some field
			    		    noteEditor.focus();	// set focus
				    	    focusSet	        = true;
				        }		    // need focus in some field
                    }
                    else
                    {               // not using tinyMCE 
    				    element.addEventListener('change', change);	// default
	    			    if (!focusSet)
		    		    {		    // need focus in some field
			    		    element.focus();	// set focus
				    	    focusSet	        = true;
				        }		    // need focus in some field
                    }
				    break;
				}		        // textual notes on name
				
				case 'addcitation':
				{		        // add citation to primary fact
				    element.addEventListener('click', addCitation);
				    break;
				}		        // add citation to primary fact

				case 'editcitation':
				{		        // edit citation to primary fact
				    element.addEventListener('click', editCitation);
				    break;
				}		        // edit citation to primary fact

				case 'delcitation':
				{		        // edit citation to primary fact
				    element.addEventListener('click', deleteCitation);
				    break;
				}		        // edit citation to primary fact

				default:
				{
				    element.addEventListener('keydown', keyDown);
				    element.addEventListener('change', change);	// default
				    break;
				}		        // default

		    }			        // action depends upon element name
		}			            // loop through all elements in the form
    }				            // loop through all forms in page

    hideRightColumn();
}		// function loadEdit

/************************************************************************
 *  function suppressSubmit												*
 *																		*
 *  This function ensures that the form cannot be submitted in the		*
 *  normal way, for example by pressing the Enter key.					*
 ************************************************************************/
function suppressSubmit()
{
    return false;
}		// function suppressSubmit

/************************************************************************
 *  function proceedWithSubmit											*
 *																		*
 *  For testing do not intercept submit.								*
 ************************************************************************/
function proceedWithSubmit()
{
    return true;
}		// function proceedWithSubmit

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function eeKeyDown													*
 *																		*
 *  Handle key strokes that apply to the dialog as a whole.  For		*
 *  example the key combinations Ctrl-S and Alt-U are interpreted to	*
 *  apply the update, as shortcut alternatives to using the mouse to 	*
 *  click the "Update Name" button.										*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an name as a parameter		*
 ************************************************************************/
function eeKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.name;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;

    // take action based upon code
    switch (code)
    {
		case 65:
		{		// letter 'A'
		    if (e.altKey)
		    {		// alt-A
				var	button	= document.getElementById('addCitation');
				button.click();
				return false;
		    }		// alt-A
		    break;
		}		// letter 'A'

		case 67:
		{		// letter 'C'
		    if (e.altKey)
		    {		// alt-C
				var	button	= document.getElementById('Clear');
				button.click();
				return false;
		    }		// alt-C
		    break;
		}		// letter 'A'

		case 83:
		{		// letter 'S'
		    if (e.ctrlKey)
		    {		// ctrl-S
				updateName();
				return false;	// do not perform standard action
		    }		// ctrl-S
		    break;
		}		// letter 'S'

		case 85:
		{		// letter 'U'
		    if (e.altKey)
		    {		// alt-U
				updateName();
		    }		// alt-U
		    break;
		}		// letter 'U'

    }	    // switch on key code

    return true;
}		// function eeKeyDown

/************************************************************************
 *  function updateName													*
 *																		*
 *  This method is called when the user requests to update				*
 *  an name of an individual.											*
 *																		*
 *  Input:																*
 *		this	the <button id='updName'> element						*
 *		ev		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function updateName(ev)
{
    if (!ev)
    {		            // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }	            	// browser is not W3C compliant
    ev.stopPropagation();

    var	form		= this.form;
    var parms		= {};
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {		// loop through elements
		var elt	= formElts[i];
		if (elt.value && elt.name)
		    parms[elt.name]	= elt.value;
    }		// loop through elements

    // var msg	= "";
    // for(fn in parms)
    //     msg	+= fn + "='" + parms[fn] + "', ";
    // alert("editName.js: updateName: parms=" + msg);

    // invoke script to update Name and return XML result
    HTTP.post('/FamilyTree/updateName.php',
		      parms,
		      gotName,
		      noName);
}	// function updateName

/************************************************************************
 *  function gotName													*
 *																		*
 *  This method is called when the XML file representing				*
 *  an updated name is retrieved from the database.						*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document containing name						*
 ************************************************************************/
function gotName(xmlDoc)
{
    if (xmlDoc === undefined)
    {
		alert("editName.js: gotName: xmlDoc is undefined!");
		return;
    }
    var	form		    = document.nameForm;

    var	root	        = xmlDoc.documentElement;
    if (root && root.nodeName == 'update')
    {
		//alert("editName.js: gotName: " + tagToString(root));
		var msgs	    = root.getElementsByTagName("msg");
		if (msgs.length > 0)
		{		            // have messages in reply
		    for(var j = 0; j < msgs.length; j++)
				alert("editName.js: gotName: msg=" + msgs[j].textContent);
		}		            // have messages in reply
		var cmds	    = root.getElementsByTagName("cmd");

		var	opener	    = null;
		if (window.frameElement && window.frameElement.opener)
		    opener	    = window.frameElement.opener;
		else
		    opener	    = window.opener;
		if (opener)
		{		            // invoked from an existing window
		    // reflect changes made to the main fields of the name
		    // back to the opener's form
		    for (var fi = 0; fi < opener.document.forms.length; fi++)
		    {		        // loop through forms in invoking page
				var	srcForm	= opener.document.forms[fi];
				if (srcForm.nameFeedback)
				{	        // feedback method defined on the form
				    var	parms	                    = {};
				    for (var ei = 0; ei < form.elements.length; ei++)
				    {		// copy element values to parms
						var	element	                = form.elements[ei];
						if (element.name.length > 0)
						{
						    if (element.type == 'checkbox' &&
							    !(element.checked))
							    parms[element.name]	= 0;
						    else
							    parms[element.name]	= element.value;
						}
				    }		// copy element values to parms
				    srcForm.nameFeedback(parms);
				    break;
				}	        // feedback method defined on the form
		    }		        // loop through forms in invoking page
		    closeFrame();		// close this window
		}		            // invoked from an existing window
		else
		    alert("editName.js: gotName: Not invoked as a dialog");
    }		// properly constructed XML
    else
    {		// error
		var	msg	= "Error: ";
		if (root)
		{
		    msg	+= tagToString(root);
		}
		else
		    msg += xmlDoc;
		alert ("editName.js: gotName: " + msg);
    }		// error
}		// function gotName

/************************************************************************
 *  function noName														*
 *																		*
 *  This method is called if there is no name response from the			*
 *  server.																*
 ************************************************************************/
function noName()
{
    alert("editName.js: noName: 'updateName.php' script not found");
}		// function noName

/************************************************************************
 *  function addCitation												*
 *																		*
 *  This method is called when the user requests to add 				*
 *  a citation to the name.												*
 *																		*
 *  Input:																*
 *		this	the invoking <button> element.			    			*
 *		ev		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function addCitation(ev)
{
    if (!ev)
    {		            // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }	            	// browser is not W3C compliant
    ev.stopPropagation();

    this.disabled	= true;			// prname double add cit
    var	form		= this.form;
    var	cell		= this.parentNode;	// <td>
    var row		= cell.parentNode;	// <tr>
    var sect		= row.parentNode;	// <tfoot>
    var	table		= sect.parentNode;	// <table>
    var	body		= table.tBodies[0];	// <tbody>

    // identification of the name to be cited
    var type		= form.citType.value;	// name type
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

    // use cookie to recall the specifics of the last citation added
    // as the defaults to fill in
    var	cookie		= new Cookie("familyTree");
    var	detail		= '';
    var	idsr		= 0;
    var	sourceName	= '';
    if (cookie.text)
    {			// recall last value entered
		detail		= cookie.text;
    }			// recall last value entered
    if (cookie.idsr)
    {			// recall last value entered
		idsr		= cookie.idsr;
    }			// recall last value entered
    if (cookie.sourceName)
    {			// recall last value entered
		sourceName	= cookie.sourceName;
    }			// recall last value entered
    else
    {			// backwards compatibility
		sourceName	= 'Source for IDSR=' + idsr;
    }			// backwards compatibility

    var	parms		= {"rownum"	: 0,
					   "detail"	: detail,
					   'idime'	: idime,
					   'type'	: type,
					   'idsr'	: idsr,
					   'sourceName'	: sourceName};
    var newRow		= createFromTemplate('sourceRow$rownum',
							     parms,
							     null);
    if (body == sect)
    {		// add button is in body table row
		body.insertBefore(newRow, row);
    }		// add button is in body table row
    else
    {		// add button is in footer row
		body.appendChild(newRow);
    }		// add button is in footer row


    // support popup help for the fields in the added row
    var elt	            = form.elements['Source0'];
    elt.helpDiv	        = 'SourceSel';
    actMouseOverHelp(elt);

    // set actions for detail input text field
    elt		            = form.elements['Page0'];
    elt.addEventListener('blur', createCitation);	// leave field
    elt.addEventListener('change', createCitation);	// change field
    actMouseOverHelp(elt);

    // set actions for detail input text field
    var detailTxt	    = form.elements['Page0'];

    // populate the select with the list of defined sources to 
    // in the second cell.  The name of the <select> element,
    // the numeric key of the <option> to select, and the name of
    // the <form> are passed as parameters so they can be returned
    // in the response.
    var sourceCell	= form.elements["Source0"];
    popupLoading(sourceCell);	// display loading indicator
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=Source0' +
					"&idsr=" + cookie.idsr +
					"&formname=" + form.name,
				gotSources,
				noSources);
}		// function addCitation

/************************************************************************
 *  function gotSources													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the list of sources from the database is retrieved.					*
 *																		*
 *  Parameters:															*
 *		xmlDoc	information about the defined sources as an XML			*
 *				function document										*
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
		alert("editName.js: gotSources: name value not returned from getSourcesXml.php");
		return;
    }		// name not returned
    }
    catch(e) {
		alert("editName.js: gotSources: nameElts=" + nameElts);
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
		alert("editName.js: gotSources: formname value not returned from getSourcesXml.php");
		return;
    }		// name not returned

    // the form element in the web page
    var	form		= document.forms[formname];

    // get the list of sources from the XML file
    var newOptions	= xmlDoc.getElementsByTagName("source");

    // locate the selection element in the web page to be updated
    var	elt		= form.elements[name];
    if (elt == null)
    {
		var msg	= "";
		for(var i=0; i < form.elements.length; i++)
		{
		    msg += form.elements[i].name + ", ";
		    if (form.elements[i].name == name)
		    {
				elt	= form.elements[i];
				break;
		    }
		}
		if (elt == null)
		{		// elt still null
		alert("editName.js: gotSources: could not find named element " +
				name + ", element names=" + msg);
		return;
		}		// elt still null
    }

    // purge old options on the select if any
    if (elt.options)
		elt.options.length	= 0;	// purge old options if any
    else
		alert("editName.js: gotSources:" + tagToString(elt));

    hideLoading();	// hide loading indicator

    // create a new HTML Option object to represent the ability to
    // create a new source and add it to the Select as the first option
    option	= addOption(elt,	// Select element
					    'Add New Source',	// text value to display
					    -1);	// key to request add
    elt.addEventListener('change', checkForAdd);

    // customize selection
    elt.size	= 10;	// height of selection list

    // add the options from the XML file to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {		// loop through source nodes
		var	node	= newOptions[i];

		// get the text value to display to the user
		// this is the name of the source
		var	text	= node.textContent;

		// get the "id" attribute, this is the IDSR value identifying
		// the source.  It becomes the value of the Option. 
		var	value	= node.getAttribute("id");
		if ((value == null) || (value.length == 0))
		{		// cover our ass
		    value		= text;
		}		// cover our ass

		// create a new HTML Option object and add it to the Select
		option	= addOption(elt,	// Select element
					    text,	// text value to display
					    value);	// unique key of source record

		// select the last source chosen by the user
		if (idsr &&
		    (value == idsr))
				option.selected	= true;

    }		// loop through source nodes

    elt.focus();		// give selection list the focus
}		// function gotSources

/************************************************************************
 *  function noSources													*
 *																		*
 *  This method is called if there is no sources script on the server.	*
 ************************************************************************/
function noSources()
{
    alert("editName.js: getSourcesXml.php not found on server");
}		// function noSources

/************************************************************************
 *  function createCitation												*
 *																		*
 *  The user has requested to add a citation and supplied all of		*
 *  the required information.											*
 *																		*
 *  Parameters:															*
 *		this		the input element for which this is the				*
 *					onchange or onblur method						    *
 ************************************************************************/
function createCitation(ev)
{
    var	rownum		= this.name.substring(4);

    // prname double invocation
    this.removeEventListener('change', createCitation);
    this.onblur		= null;

    // get parameters from the form containing this cell
    var	form		= this.form;		// form containing element
    var formName	= form.name;		// name of the form
    // key of associated record
    var idime		= form.elements['idime' + rownum].value;
    var addButton	= document.getElementById('addCitation' + idime);
    if (!addButton)
		addButton	= document.getElementById('AddCitation');
    if (addButton)
		addButton.disabled	= false;	// re-enable adding citations
    addButton		= document.getElementById('addCitationDeathCause');
    if (addButton)
		addButton.disabled	= false;	// re-enable adding citations

    // type of name within record
    var type		= form.elements['type' + rownum].value;	
    var pageText	= this.value;		// value of page element
    var	cell		= this.parentNode;	// cell containing page element
    var row		= cell.parentNode;	// row containing page element
    var cell2		= row.cells[1];		// 2nd cell in same row
    var sourceSel	= form.elements['Source' + rownum];
    var sourceOpt	= null;
    if (sourceSel)
    {
		sourceOpt	= sourceSel.options[sourceSel.selectedIndex];

		var idsr		= 0;
		if (sourceOpt)
		    idsr		= sourceOpt.value;

		if (idsr > 0)
		{		// existing source IDSR
		    // update the cookies for the IDSR value of the last source
		    // requested and the citation page text
		    var	cookie		= new Cookie("familyTree");
		    cookie.idsr		= idsr;
		    cookie.text		= pageText;
		    cookie.sourceName	= sourceOpt.innerHTML;
		    cookie.store(10);		// keep for 10 days

		    // parameters passed by method='post'
		    var parms		= {
						"idime" 	: idime,
						"type"		: type,
						"idsr"		: idsr,
						"page"		: pageText,
						"row"		: rownum,
						"formname"	: formName}; 

		    // send the request to add a citation to the server requesting
		    // an XML response
		    HTTP.post('/FamilyTree/addCitXml.php',
				      parms,
				      gotAddCit,
				      noAddCit);
		}		// existing source IDSR
		else
		{		// create a new source
		    openFrame("source",
				      "editSource.php?idsr=0&form=" + formName +
							"&select=" + sourceSel.name,
				      nameChildFrameClass);
		}		// create a new source
    }
    else
    {			// source <select> tag not found
		var	names	= "";
		for(var n in form)
		    names	+= n + ", ";
		alert("editName.js: createCitation: form.elements[" + names +
							"], name='Source" + rownum + "'");
    }			// source <select: tag not found
}	// function createCitation

/************************************************************************
 *  function gotAddCit													*
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
		    var	rowNum		= xmlRoot.getAttribute('row');
		    var	formname	= xmlRoot.getAttribute('formname');
		    var	form		= document.forms[formname];
    
		    var	parmsList	= xmlRoot.getElementsByTagName('parms');
		    if (parmsList.length > 0)
		    {
				parms	= getParmsFromXml(parmsList[0]);
		    }
		    else
				parms	= {};
    
		    var	sourceList	= xmlRoot.getElementsByTagName('source');
		    if (sourceList.length > 0)
				parms['title']	= sourceList[0].textContent;
		    else
				parms['title']	= '';
    
		    var	idsx		= '';
		    var	idsxList	= xmlRoot.getElementsByTagName('idsx');
		    if (idsxList.length > 0)
				idsx		= idsxList[0].textContent;
		    parms['idsx']	= idsx;
    
		    // locate elements in web page to be updated
		    var	trowName	= 'sourceRow' + rowNum;
		    var	trow	= document.getElementById(trowName);
		    if (trow)
		    {
				var	tbody	= trow.parentNode;
				var	nextRow	= trow.nextSibling;

				tbody.removeChild(trow);	// remove temporary row
    
				var	newRow	= createFromTemplate('sourceRow$idsx',
								     parms,
								     null);
				tbody.insertBefore(newRow, nextRow);
    
				// activate functionality of buttons
				var edit	= document.getElementById('editCitation'+ idsx);
				edit.addEventListener('click', editCitation);
				var del		= document.getElementById('delCitation' + idsx);
				del.addEventListener('click', deleteCitation);
		    }
		}		// valid response
		else	// unexpected response
		    alert("editName.js: gotAddCit: xmlRoot='" +
							tagToString(xmlRoot) + "'");
    }
    else
		alert("editName.js: gotAddCit: xmlDoc='" + xmlDoc + "'");
}		// function gotAddCit

/************************************************************************
 *  function noAddCit													*
 *																		*
 *  This method is called if there is no add citation response			*
 *  file from the server.												*
 ************************************************************************/
function noAddCit()
{
}		// function noAddCit

/************************************************************************
 *  function editCitation												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  a citation to a source for an name.									*
 *																		*
 *  Input:																*
 *		this	instance of <button> tag						        *
 *		ev		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function editCitation(ev)
{
    if (!ev)
    {		            // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }	            	// browser is not W3C compliant
    ev.stopPropagation();

    var	form		= this.form;
    var	idsx		= this.id.substr(12);

    openFrame("citation",
		      "editCitation.php?idsx=" + idsx + '&formId=' + form.id, 
		      nameChildFrameClass);
}	// function editCitation

/************************************************************************
 *  function deleteCitation												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  a citation to a source for an name.									*
 *																		*
 *  Input:																*
 *		this	<button>					        					*
 *		ev		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function deleteCitation(ev)
{
    if (!ev)
    {		            // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }	            	// browser is not W3C compliant
    ev.stopPropagation();

    var	form		= this.form;
    var	idsx		= this.id.substr(11);

    var parms		= {"idsx"	: idsx,
					   "rownum"	: idsx,
					   "formname"	: form.name}; 

    // invoke script to update Name and return XML result
    HTTP.post('/FamilyTree/deleteCitationXml.php',
		      parms,
		      gotDeleteCit,
		      noDeleteCit);
}	// function deleteCitation

/************************************************************************
 *  function gotDeleteCit												*
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
		alert ("editName.js: gotDeleteCit: Error: " + msg);
    }		// error unexpected document
}		// function gotDeleteCit

/************************************************************************
 *  function noDeleteCit												*
 *																		*
 *  This method is called if there is no delete citation response		*
 *  file.																*
 ************************************************************************/
function noDeleteCit()
{
    alert("editName.js: deleteCitationXml.php not found on server");
}		// function noDeleteCit

/************************************************************************
 *  function updateCitation												*
 *																		*
 *  This method is called by the editCitation.php script to feed back	*
 *  the results so they can be reflected in this page.					*
 *																		*
 *  Parameters:															*
 *		this			instance of <form> containing citation list		*
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
    var form		= this;
    var sourceElement	= document.getElementById("Source" + idsx);
    var pageElement	= document.getElementById("Page" + idsx);
    if (sourceElement)
		sourceElement.value	= sourceName;
    else
		alert("editName.js: updateCitation: unable to get element id='Source"+
				idsx + "'");
    if (pageElement)
		pageElement.value	= page;
    else
		alert("editName.js: updateCitation: unable to get element id='Page"+
				idsx + "'");
}		// function updateCitation

/************************************************************************
 *  function checkForAdd												*
 *																		*
 *  The user has selected a different option in the selection list of	*
 *  sources.															*
 *																		*
 *  Parameters:															*
 *		this			<select> element for which this is the			*
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
				  nameChildFrameClass);
    }		// create new source
}		// function checkForAdd

/************************************************************************
 *  function clearNotes													*
 *																		*
 *  This method is called when the user requests to clear the note		*
 *  area to empty.														*
 *																		*
 *  Input:																*
 *		this	<button type='button' id='Clear'>					    *
 *		ev		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function clearNotes(ev)
{
    if (!ev)
    {		            // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }	            	// browser is not W3C compliant
    ev.stopPropagation();

    tinyMCE.get('note').setContent("");
}	// function clearNotes

/************************************************************************
 *  function sourceCreated												*
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
}	// function sourceCreated

