/************************************************************************
 *  BirthRegResponse.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  BirthRegResponse.php												*
 *																		*
 *  History:															*
 *		2011/00/27		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/12/24		simplify button implementation					*
 *		2014/08/28		add support for Delete button					*
 *		2014/10/11		ask user to confirm delete of registration		*
 *		2014/12/18		support all provinces							*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/02/20      shrink browse rows to match width of table      *
 *		2019/04/07      ensure that the paging lines can be displayed   *
 *		                within the visible portion of the browser.      *
 *		2019/06/29      first parameter of displayDialog removed        *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    var	element;
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];
		trace	+= "<form ";
		if (form.name.length > 0)
		    trace	+= "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	+= "id='" + form.id + "' ";
		trace	+= ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    trace += "<" + element.nodeName + " ";
	    	if (element.name.length > 0)
		        trace	+= "name='" + element.name + "' ";
		    if (element.id.length > 0)
		        trace	+= "id='" + element.id + "' ";
		    trace	+= ">";
		    element.onkeydown	= keyDown;
    
		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	// set mouseover on containing cell
		    else
		    {	// set mouseover on input element itself
				element.onmouseover		= eltMouseOver;
				element.onmouseout		= eltMouseOut;
		    }	// set mouseover on input element itself
    
		    if (element.id.substring(0, 6) == 'Action')
		    {
				element.helpDiv	        = 'Action';
				element.onclick	        = showReg;
		    }
		    else
		    if (element.id.substring(0, 6) == 'Delete')
		    {
				element.helpDiv	        = 'Delete';
				element.onclick	        = deleteReg;
		    }
		}	        // loop through all elements in the form
    }		        // loop through all forms

}	        	// function onLoad

/************************************************************************
 *  function showReg													*
 *																		*
 *  When an Action button is clicked this function displays the			*
 *  page to edit or display details of the registration.				*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Action...'>					*
 ************************************************************************/
function showReg()
{
    var	form	= this.form;
    var	domain	= form.RegDomain.value;
    var	rownum	= this.id.substring(6);
    var regyear	= rownum.substring(0,4);
    var regnum	= rownum.substring(4);
    location	= 'BirthRegDetail.php?RegDomain=' + domain + '&RegYear=' + regyear + '&RegNum=' + regnum;
    return false;
}		        // function showReg

/************************************************************************
 *  function deleteReg													*
 *																		*
 *  When a Delete button is clicked this function invokes a server		*
 *  to delete the registration.											*
 *																		*
 *  Input:																*
 *		this	<button type=button id='Delete...'>						*
 ************************************************************************/
function deleteReg(ev)
{
    ev.stopPropagation();
    var	form			= this.form;
    var	rownum			= this.id.substring(6);
    var	domain			= form.RegDomain.value;
    var regyear			= rownum.substring(0,4);
    var regnum			= rownum.substring(4);

    var parms		    = {"regdomain"	: domain,
						   "regyear"	: regyear,
						   "regnum"	: regnum,
						   "formname"	: form.name, 
						   "template"	: ""};

    if (debug != 'n')
		parms["debug"]	= debug;

    // ask user to confirm delete
	displayDialog('RegDel$template',
			      parms,
			      this,		        // position relative to
			      confirmDelete);	// 1st button confirms Delete
}	        	// function deleteReg

/************************************************************************
 *  function confirmDelete												*
 *																		*
 *  This method is called when the user confirms the request to delete	*
 *  a registration.														*
 *																		*
 *  Input:																*
 *		this		<button id='confirmDelete...'>						*
 ************************************************************************/
function confirmDelete()
{
    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	regnum		= this.id.substr(13);
    var	regdomain	= form.elements['regdomain'].value;
    var	regyear		= form.elements['regyear'].value;
    var	formname	= form.elements['formname'].value;

    // hide the dialog
    dialogDiv.style.display	= 'none';


    var script	    = 'deleteBirthRegXml.php';
    var	parms	    = { 'RegDomain'	: regdomain,
				        'RegYear'	: regyear,
				        'RegNum'	: regnum,
				        'rownum'	: regyear + "" + regnum};
    if (debug != 'n')
		parms["debug"]	= debug;

    // update the citation in the database
    HTTP.post(  script,
				parms,
				gotDeleteReg,
				noDeleteReg);
    return false;		// suppress default action for button
}	        	// function confirmDelete

/************************************************************************
 *  function gotDeleteReg												*
 *																		*
 *  This method is called when the XML file representing				*
 *  the deletion of the registration from the database is retrieved.	*
 *																		*
 *  Input:																*
 *		xmlDoc		response document									*
 ************************************************************************/
function gotDeleteReg(xmlDoc)
{
    if (xmlDoc === undefined)
    {
		alert("BirthRegResponse.js: gotDeleteReg: xmlDoc is undefined!");
    }
    else
    {
		var	root	= xmlDoc.documentElement;
		for (var i = 0; i < root.childNodes.length; i++)
		{				    // loop through all children
		    var	elt	= root.childNodes[i];
		    if (elt.nodeType == 1)
		    {				// tag
				if (elt.nodeName == 'parms')
				{		    // parms
				    for (var j = 0; i < elt.childNodes.length; i++)
				    {		// loop through all children
				        var	child	= elt.childNodes[i];
				        if (child.nodeType == 1)
				        {	// tag
						    if (child.nodeName == 'rownum')
						    {	// rownum
								var rownum	= child.textContent.trim();
								// remove Delete button
								var butid	= 'Delete' + rownum;
								var button	= document.getElementById(butid);
								var cell	= button.parentNode;
								cell.removeChild(button);

								// blank out text columns
								var rowNode	= cell.parentNode;
								var rowCells	= rowNode.cells;
								for (var ic = 3; ic < rowCells.length; ic++)
								{		// loop through columns
								    cell	= rowCells[ic];
								    while (cell.firstChild)
									cell.removeChild(cell.firstChild);
								}		// loop through columns
						    }	// rownum
				        }	// tag
				    }		// loop through all children
				}		    // parms
		    }				// tag
		}			    	// loop through all children

    }
}	        	// function gotDeleteReg

/************************************************************************
 *  function noDeleteReg												*
 *																		*
 *  This method is called if there is no delete registration script.	*
 ************************************************************************/
function noDeleteReg()
{
    alert("BirthRegResponse.js: noDeleteReg: " +
				"script 'deleteBirthRegXml.php' not found on server");
}		// function noDeleteReg
