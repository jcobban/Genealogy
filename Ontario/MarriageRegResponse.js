/************************************************************************
 *  MarriageRegResponse.js												*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  MarriageRegResponse.php												*
 *																		*
 *  History:															*
 *		2011/00/27		created											*
 *		2013/01/09		add support for button to delete marriages		*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/10/11		ask user to confirm delete of registration		*
 *						use script deleteMarriageRegXml.php to delete	*
 *		2015/07/01		update page DOM as a result of delete request	*
 *						instead of reloading page						*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/01/31      permit using pageUp and pageDown to move        *
 *		                through pages of response                       *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/06/29      first parameter of displayDialog removed        *
 *		2020/12/08      pass lang selection to display detail script    *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
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
    var trace		= '';
    var	element;
    for (var fi = 0; fi < document.forms.length; fi++)
    {		    // loop through all forms
		var form	= document.forms[fi];
		trace		+= "<form ";
		if (form.name.length > 0)
		    trace	+= "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	+= "id='" + form.id + "' ";
		trace	+= ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	    // loop through all elements of form
		    element		= form.elements[i];
		    trace += "<" + element.nodeName + " ";
		if (element.name.length > 0)
		    trace	+= "name='" + element.name + "' ";
		if (element.id.length > 0)
		    trace	+= "id='" + element.id + "' ";
    		trace	+= ">";
		    element.onkeydown	= keyDown;
    
		    if (element.id.substring(0, 6) == 'Action')
		    {
    			element.helpDiv	= 'Action';
    			element.onclick	= showReg;
		    }
		    else
		    if (element.id.substring(0, 6) == 'Delete')
		    {
    			element.helpDiv	= 'Delete';
    			element.onclick	= deleteReg;
		    }
		}	    // loop through all elements in the form
    }		    // loop through all forms
}		// function onLoad

/************************************************************************
 *  function showReg													*
 *																		*
 *  When a Action button is clicked this function displays the			*
 *  page to edit or display details of the registration.				*
 *																		*
 *  Input:																*
 *		this	<button type=button id='Action...'>						*
 ************************************************************************/
function showReg(event)
{
    event.stopPropagation();
    var	form	= this.form;
    var	domain	= form.RegDomain.value;
    var	recid	= this.id.substring(6);
    var regyear	= recid.substring(0,4);
    var regnum	= recid.substring(4);
    var	lang		= 'en';
    if ('lang' in args)
		lang		= args['lang'];
    // display details
    location	= 'MarriageRegDetail.php?Domain=' + domain + 
						'&RegYear=' + regyear +
						'&RegNum=' + regnum + '&lang=' + lang;
    return false;
}		// function showReg

/************************************************************************
 *  function deleteReg													*
 *																		*
 *  When a Delete button is clicked this function invokes a server		*
 *  to delete the registration.											*
 *																		*
 *  Input:																*
 *		this	<button type=button id='Delete...'>						*
 ************************************************************************/
function deleteReg(event)
{
    event.stopPropagation();
    var	form	= this.form;
    var	recid	= this.id.substring(6);
    var	domain	= form.RegDomain.value;
    var regyear	= recid.substring(0,4);
    var regnum	= recid.substring(4);

    var parms		= {"regdomain"	: domain,
    				   "regyear"	: regyear,
	    			   "regnum"	: regnum,
		    		   "formname"	: form.name, 
			    	   "template"	: "",
				       "msg"	:
	        			"Are you sure you want to delete this registration?"};

    if (debug != 'n')
		parms["debug"]	= debug;

    // ask user to confirm delete
	displayDialog('RegDel$template',
    			  parms,
    		      this,		        // position relative to
    		      confirmDelete);	// 1st button confirms Delete
}		// function deleteReg

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
    var script	= 'deleteMarriageRegXml.php';
    var	parms	= { 'RegDomain'	: regdomain,
			    'RegYear'	: regyear,
			    'RegNum'	: regnum,
			    'rownum'	: regyear + "" + regnum};
    if (debug != 'n')
		parms["debug"]	= debug;

    // invoke script to update Event and return XML result
    HTTP.post(script,
		      parms,
		      gotDeleteReg,
		      noDeleteReg);

    return false;
}		// function deleteReg

/************************************************************************
 *  function gotDeleteReg												*
 *																		*
 *  The XML document representing the results of the request to 		*
 *  delete the marriage registration has been received.					*
 *																		*
 *  Input:																*
 *		xmlDoc			XML document with results of delete				*
 ************************************************************************/
function gotDeleteReg(xmlDoc)
{
    var	topXml	= xmlDoc.documentElement;
    if (topXml && typeof(topXml) == "object" && topXml.nodeName == 'deleted')
    {			// valid response
		var	parms	= topXml.firstChild;
		for(; parms; parms = parms.nextSibling)
		    if (parms.nodeName == 'parms')
			break;
		var child	= parms.firstChild;
		for(; child; child = child.nextSibling)
		    if (child.nodeName.toLowerCase() == 'rownum')
			break;
		if (child)
		{			// have the rownum identifier
		    var rownum	= child.textContent;
		    var button	= document.getElementById('Delete' + rownum);
		    if (button)
		    {			// have the delete button
			var cell	= button.parentNode;
			cell.removeChild(button);
			var row		= cell.parentNode;
			for(var ci = 3; ci < row.cells.length; ci++)
			{		// clear out all other cells in row
			    cell		= row.cells[ci];
			    cell.innerHTML	= '';
			}		// clear out all other cells in row
		    }			// have the delete button
		    else
			alert("MarriageRegResponse.js: gotDeleteReg: " +
				"Logic Error: cannot find <button id='Delete" +
				rownum + "'> in page");
		}			// have the rownum identifier
		else
		    location.reload();
    }			// valid response
    else
    {
		if (topXml && typeof(topXml) == "object")
		    alert("MarriageRegResponse.js: gotDeleteReg: " +
                    new XMLSerializer().serializeToString(topXml));
		else
		    alert("MarriageRegResponse.js: gotDeleteReg: '" + xmlDoc + "'");
    }
}		// function gotDeleteReg

/************************************************************************
 *  function noDeleteReg												*
 *																		*
 *  This method is called if there is no delete registration script.	*
 ************************************************************************/
function noDeleteReg()
{
    alert("MarriageRegResponse.js: noDeleteReg: " +
		  "script 'deleteMarriageRegXml.php' not found on server");
}       // function noDeleteReg
