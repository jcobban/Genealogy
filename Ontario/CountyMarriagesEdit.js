/************************************************************************
 *  CountyMarriagesEdit.js												*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountyMarriagesEdit.php												*
 *																		*
 *  History:															*
 *		2016/01/30		created											*
 *		2016/03/19		use popup menus for selection					*
 *		2016/03/22		use longer given name prefix for common names	*
 *		2016/05/31		use common function dateChanged					*
 *		2016/11/14		add dynamic support for new columns				*
 *						copy value of date and license type to bride	*
 *						share initialization code between initial load	*
 *						and adding new lines							*
 *		2017/01/11		add ability to hide columns						*
 *		2017/01/12		include age when calculating birth year			*
 *						for link button									*
 *		2017/01/13		add "Clear" button to remove linkage			*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
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
    // including support for context specific help
    var	element;
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];

		    initElement(element);
		}		// loop through all elements in the form
    }			// loop through all forms

    // enable support for hiding and revealing columns within a table
    var dataTable		    = document.getElementById("dataTable");
    var tblHdr		        = dataTable.tHead;
    var tblHdrRow	        = tblHdr.rows[0];
    for(i = 0; i < tblHdrRow.cells.length; i++)
    {			// loop through cells of header row
		var th			    = tblHdrRow.cells[i];
		th.onclick		    = columnClick;
		th.oncontextmenu	= columnWiden;
    }			// loop through cells of header row

    hideRightColumn();
}		// function onLoad

/************************************************************************
 *  function linkToTree													*
 *																		*
 *  When a Link button is clicked a window is opened either to display	*
 *  an existing individual in the tree or to search for a match.		*
 *																		*
 *  Input:																*
 *		$this				<button type=button id='Link....'			*
 ************************************************************************/
function linkToTree()
{
    var	form		= this.form;
    var	rownum		= this.id.substring(4);
    var	element, idir, element, script;

    element		= document.getElementById('IDIR' + rownum);
    if (element)
		idir		= element.value;
    else
		idir		= 0;

    if (idir > 0)
    {				// existing match to tree
		window.open('../FamilyTree/Person.php?idir=' + idir);
    }				// existing match to tree
    else
    {				// search for match
		var	msgDiv		= document.getElementById('msgDiv');
		hide(msgDiv);
		var	role		= form.elements['Role' + rownum].value;
		var	surname		= form.elements['Surname' + rownum].value;
		var	given		= form.elements['GivenNames' + rownum].value;
		if (given.substring(0,4) == 'Mary' ||
		    given.substring(0,4) == 'John')
		    given		= given.substring(0,4);
		else
		    given		= given.substring(0,2);
		var	date		= form.elements['Date' + rownum].value;
		var	ageElement	= form.elements['Age' + rownum];
		var	age		= '';
		if (ageElement)
		    age			= ageElement.value;
		var	birthmin	= 1750;
		var	birthmax	= 1856;
		if (date.length >= 4)
		{
		    var matches		= /\d\d\d\d/.exec(date);
		    if (Array.isArray(matches))
		    {	// year of marriage
			var year	= parseInt(matches[0]);

			if (age.length > 0)
			{
			    birthmin	= year - (age - 0 + 5);
			    birthmax	= year - (age - 5);
			}
			else
			{
			    birthmin	= year - 80;
			    birthmax	= year - 16;
			}
		    }	// year of marriage
		}

		var	sex	= 'M';
		if (role == 'B')
		    sex		= 'F';

		var url	= "/FamilyTree/getIndivNamesXml.php?Surname=" +
				encodeURIComponent(surname) +
				"&GivenName=" + encodeURIComponent(given) +
				"&Sex=" + sex +
				"&BirthMin=" + birthmin +
				"&BirthMax=" + birthmax +
				"&buttonId=" + this.id +
				"&includeSpouse=Y" +
				"&incMarried=yes&loose=yes";

		HTTP.getXML(url,
			    gotIdir,
			    noIdir);
    }		// search for matches
    return false;
}		// function linkToTree

/************************************************************************
 *  function gotIdir													*
 *																		*
 *  The XML response to the database query for matching individuals has	*
 *  been returned.														*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document										*
 ************************************************************************/
function gotIdir(xmlDoc)
{
    //alert("CensusForm.js: gotIdir: xmlDoc=" + tagToString(xmlDoc));
    var	rootNode	= xmlDoc.documentElement;
    var	buttonId	= rootNode.getAttribute("buttonId");
    var	button		= document.getElementById(buttonId);
    if (button === null)
    {
		var	msgDiv	= document.getElementById('msgDiv');
		hide(msgDiv);
		alert("CensusForm.js: gotIdir: unable to find element with id='" +
			buttonId + "' rootNode=" + tagToString(rootNode));
		return;
    }

    var	form		= button.form;
    var	line		= buttonId.substring(4);
    var	surname		= form.elements['Surname' + line].value;
    var	givennames	= form.elements['GivenNames' + line].value;
    var	birthmin	= 1750;
    var birthmax	= 1852;

	var parmElts	= xmlDoc.getElementsByTagName("parms");
	var parmElt	= parmElts[0];
	for (var elt = parmElt.firstChild; elt; elt = elt.nextSibling)
	{
	    if (elt.nodeType == 1)
	    {		// is an element node
			switch (elt.nodeName.toLowerCase())
			{	// act on specific parameter names
			    case 'birthmin':
			    {
				    birthmin	= elt.textContent;
				    break;
			    }

			    case 'birthmax':
			    {
				    birthmax	= elt.textContent;
				    break;
			    }

			}	// act on specific parameter names
	    }		// is an element node
	}		// loop through elements under <parms>

	// substitutions into the template
	var parms	    = {"sub"	    : "",
		    		   "surname"	: surname,	
		    		   "givenname"	: givennames,
				       'birthmin'	: birthmin,
				       'birthmax'	: birthmax,
		    		   "line"	    : line};

	var matches	= xmlDoc.getElementsByTagName("indiv");
	if (matches.length > 0)
	{		// have some matching entries
	    return displaySelectIdir('idirChooserForm$sub',
        					     parms,
        					     button,
        					     closeIdirDialog,
        					     matches);
	}		// have some matching entries
	else
	{		// have no matching entries
	    return displayDialog('idirNullForm$sub',
        					 parms,
        					 button,
        					 null);		// default close dialog
	}		// have no matching entries
}		// function gotIdir

/************************************************************************
 *  function noIdir														*
 *																		*
 *  The database server was unable to respond to the query.				*
 ************************************************************************/
function noIdir()
{
    alert("CensusForm.js: noIdir: " +
		  "unable to find getIndivNamesXml.php script on server");
}		// function noIdir

/************************************************************************
 *  function displaySelectIdir											*
 *																		*
 *  This function displays a customized dialog for choosing from		*
 *  a list of individuals who match the individual described by the		*
 *  current line of the census.											*
 *																		*
 *  Input:																*
 *		templateId		identifier of an HTML element that provides the	*
 *						structure and constant strings to be laid out	*
 *						in the dialog									*
 *		parms			an object containing values to substitute for	*
 *						symbols ($xxxx) in the template					*
 *		element			an HTML element used for positioning the		*
 *						dialog for the user.  This is normally the 		*
 *						<button> for the user to request the dialog.	*
 *		action			onclick action to set for 1st (or only) button	*
 *						in the dialog.  If null the default action is	*
 *						to just hide the dialog.						*
 *		matches			array of XML <indiv> tags						*
 ************************************************************************/
function displaySelectIdir(templateId,
        				   parms,
        				   element,
        				   action,
        				   matches)
{
    var dialog  = displayDialog(templateId,
		    	                parms,
		    	                element,
		    	                action,
		    	                true);
    if (dialog)
    {
		// update the selection list with the matching individuals
		var select	= document.getElementById("chooseIdir");
		select.onchange	= idirSelected;
		//select.onclick	= function() {alert("select.onclick");};

		// add the matches
		for (var i = 0; i < matches.length; ++i)
		{	// loop through the matches
		    var	indiv	= matches[i];

		    // get the "id" attribute
		    var	value		= indiv.getAttribute("id");
		    var	surname		= "";
		    var	maidenname	= "";
		    var	givenname	= "";
		    var	gender		= "";
		    var	birthd		= "";
		    var	deathd		= "";
		    var	parents		= "";
		    var	spouses		= "";

		    for (var child = indiv.firstChild;
			 child;
			 child = child.nextSibling)
		    {		// loop through all children of indiv
			if (child.nodeType == 1)
			{	// element node
			    switch(child.nodeName)
			    {	// act on specific child
				case "surname":
				{
				    surname	= child.textContent;
				    break;
				}

				case "maidenname":
				{
				    maidenname	= child.textContent;
				    break;
				}

				case "givenname":
				{
				    givenname	= child.textContent;
				    break;
				}

				case "gender":
				{
				    gender	= child.textContent;
				    break;
				}

				case "birthd":
				{
				    birthd	= child.textContent;
				    break;
				}

				case "deathd":
				{
				    deathd	= child.textContent;
				    break;
				}

				case "parents":
				{
				    parents	= child.textContent;
				    break;
				}

				case "families":
				{
				    spouses	= child.textContent;
				    break;
				}

				default:
				{
				    // alert("CensusForm.js:displaySelectIdir: " +
				    //	  "nodeName='" + child.nodeName + "'");
				    break;
				}
			    }	// act on specific child
			}	// element node
		    }		// loop through all children of indiv

		    var text	= surname;
		    if (maidenname != surname)
			text	+= " (" + maidenname + ")";
		    text	+= ", " + givenname + "(" + 
				   birthd + "-" +
				   deathd + ")";
		    if (parents.length > 0)
			text	+= ", child of " + parents;
		    if (spouses.length > 0)
			text	+= ", spouse of " + spouses;

		    // add a new HTML Option object
		    addOption(select,	// Select element
			      text,	// text value 
			      value);	// unique key
		}	// loop through the matches

		select.selectedIndex	= 0;

		// show the dialog
		dialog.style.visibility	= 'visible';
		dialog.style.display	= 'block';
		// the following is a workaround for a bug in FF 40.0 and Chromium
		// in which the onchange method of the <select> is not called when
		// the mouse is clicked on an option
		for(var io=0; io < select.options.length; io++)
		{
		    var option	= select.options[io];
		    option.addEventListener("click", function() {this.selected = true; this.parentNode.onchange();});
		}
		select.focus();
		return true;
    }		// template OK
    else
		return false;
}		// function displaySelectIdir

/************************************************************************
 *  function idirSelected												*
 *																		*
 *  This is the onchange method of the select in the popup to choose	*
 *  the individual to associated with the current line.					*
 *																		*
 *  Input:																*
 *		this		= <select id='chooseIdir'>							*
 ************************************************************************/
function idirSelected()
{
    var	select	= this;
    var	idir	= 0;
    var	index	= select.selectedIndex;
    if (index >= 0)
    {
		var	option	= select.options[index];
		idir	= option.value;
    }
    var	form	= this.form;	// <form name='idirChooserForm'>

    for(var ie = 0; ie < form.elements.length; ie++)
    {		// search for choose button
		var	element	= form.elements[ie];
		if (element != select &&
		    element.id && element.id.length >= 6 && 
		    element.id.substring(0,6) == "choose")
		{	// have the button
		    if (idir == 0)
			element.innerHTML	= 'Cancel';
		    else
			element.innerHTML	= 'Select';
		}	// have the button
    }		// search for choose button
}		// function idirSelected

/************************************************************************
 *  function closeIdirDialog											*
 *																		*
 *  The user clicked on the button to close the IDIR dialog.			*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function closeIdirDialog()
{
    var	form	= this.form;
    var select	= form.chooseIdir;
    if (select)
    {		// select for IDIR present
		if (select.selectedIndex >= 0)
		{	// option chosen
		    var option	= select.options[select.selectedIndex];	
		    var idir	= option.value;
		    if (idir > 0)
		    {	// individual chosen
			var line	= this.id.substring(6);
			var mainForm	= document.countyForm;
			mainForm.elements["IDIR" + line].value		= idir;
			// remove "find" button from cell
			var findButton	= mainForm.elements["Link" + line];
			var cell	= findButton.parentNode;
			cell.removeChild(findButton);	
			// add "tree" linked button
			var parms		= {'row'	: line};
			var template		= document.getElementById('Link$row');
			var linkButton		= createFromTemplate(template,
								     parms,
								     null);
			var newButton		= cell.appendChild(linkButton);
			newButton.onclick	= linkToTree;
			// add "clear" button to remove link to tree
			var template		= document.getElementById('Clear$row');
			var clearButton		= createFromTemplate(template,
								     parms,
								     null);
			newButton		= cell.appendChild(clearButton);
			newButton.onclick	= clearFromTree;
		    }	// individual chosen
		}	// option chosen
    }		// select for IDIR present

    var	msgDiv	= document.getElementById('msgDiv');
    hide(msgDiv);

    // suppress default action
    return false;
}		// function closeIdirDialog

/************************************************************************
 *  function clearFromTree												*
 *																		*
 *  The user clicked on the button to remove an existing linkage to		*
 *  the family tree.													*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function clearFromTree()
{
    var	form	= this.form;
    var line	= this.id.substring(this.id.length - 2);
    // clear linkage
    form.elements["IDIR" + line].value		= 0;
    // remove "Tree" button from cell
    var treeButton	= form.elements["Link" + line];
    var cell		= treeButton.parentNode;
    cell.removeChild(treeButton);	
    // remove "Clear" button from cell
    var clearButton	= form.elements["Clear" + line];
    cell.removeChild(clearButton);	
    // add "find" linked button
    var parms		= {'rowf'	: line};
    var template	= document.getElementById('Link$rowf');
    var findButton	= createFromTemplate(template,
    				     parms,
    				     null);
    var newButton	= cell.appendChild(findButton);
    newButton.onclick	= linkToTree;

    // suppress default action
    return false;
}		// function clearFromTree

/************************************************************************
 *  function showDetails												*
 *																		*
 *  When a Details button is clicked this function displays the			*
 *  detailed information about the marriage.							*
 *  Temporarily this just displays the two rows associated with the		*
 *  current item.														*
 *																		*
 *  Input:																*
 *		$this		<button type=button id='Details....'				*
 ************************************************************************/
function showDetails()
{
    var	form		= this.form;
    var	rownum		= this.id.substring(7);
    var	domain, volume, reportNo, element;

    if (form.Domain)
		domain		= form.Domain.value;
    else
    {
		element		= form.elements['Domain' + rownum];
		if (element)
		    domain	= element.value;
		else
		    alert("showDetails: cannot find Domain field elements['Domain" +rownum + "']");
    }

    if (form.Volume)
		volume		= form.Volume.value;
    else
    {
		element		= form.elements['Volume' + rownum];
		if (element)
		    volume	= element.value;
		else
		    alert("showDetails: cannot find Volume field elements['Volume" +rownum + "']");
    }

    if (form.ReportNo)
		reportNo		= form.ReportNo.value;
    else
    {
		element		= form.elements['ReportNo' + rownum];
		if (element)
		    reportNo	= element.value;
		else
		    alert("showDetails: cannot find ReportNo field elements['ReportNo" +rownum + "']");
    }

    var	itemNo		= form.elements['ItemNo' + rownum].value;
    var	script;
    if (domain == 'CAUC')
		script	= 'DistrictMarriagesEdit.php?Domain=' + domain +
						'&Volume=' + volume +
						'&ReportNo=' + reportNo +
						'&ItemNo=' + itemNo;
    else
		script	= 'CountyMarriagesEdit.php?Domain=' + domain +
						'&Volume=' + volume +
						'&ReportNo=' + reportNo +
						'&ItemNo=' + itemNo;
    location	= script;
    return false;
}		// function showDetails

/************************************************************************
 *  function deleteRow													*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Delete....'				*
 ************************************************************************/
function deleteRow()
{
    var	form		= this.form;
    var	rownum		= this.id.substring(6);
    var	domain, volume, reportNo, element;

    if (form.Domain)
		domain		= form.Domain.value;
    else
    {
		element		= form.elements['Domain' + rownum];
		if (element)
		    domain	= element.value;
		else
		    alert("showDetails: cannot find Domain field");
    }

    if (form.Volume)
		volume		= form.Volume.value;
    else
    {
		element		= form.elements['Volume' + rownum];
		if (element)
		    volume	= element.value;
		else
		    alert("showDetails: cannot find Volume field");
    }

    if (form.ReportNo)
		reportNo		= form.ReportNo.value;
    else
    {
		element		= form.elements['ReportNo' + rownum];
		if (element)
		    reportNo	= element.value;
		else
		    alert("showDetails: cannot find ReportNo field");
    }

    var	itemNo		= form.elements['ItemNo' + rownum].value;
    var	role		= form.elements['Role' + rownum].value;
    //alert("deleteRow: domain='" + domain + "', volume=" + volume + ", reportNo=" + report);
    var script	= 'deleteCountyMarriageXml.php';
    var	parms	= { 'Domain'	: domain,
			    'Volume'	: volume,
			    'reportNo'	: reportNo,
			    'itemNo'	: itemNo,
			    'role'	: role,
			    'rownum'	: rownum};
    if (debug != 'n')
		parms["debug"]	= debug;

    // update the citation in the database
    HTTP.post(  script,
			parms,
			gotDelete,
			noDelete);
    return false;
}		// function deleteRow

/************************************************************************
 *  function gotDelete													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the deletion of the report from the database is retrieved.			*
 *																		*
 *  Input:																*
 *		xmlDoc		response document									*
 ************************************************************************/
function gotDelete(xmlDoc)
{
    if (xmlDoc === undefined)
    {
		alert("CountyMarriagesEdit.js: gotDelete: xmlDoc is undefined!");
    }
    else
    {			// xmlDoc is defined
		var	root	= xmlDoc.documentElement;
		alert("gotDelete: " + tagToString(root));
		var	parms	= root.getElementsByTagName('parms');
		if (parms.length > 0)
		{		// have at least 1 parms element
		    parms	= parms[0];
		    var rownums	= parms.getElementsByTagName('rownum');
		    if (rownums.length > 0)
		    {		// have at least 1 rownum element
				var child	= rownums[0];
				var rownum	= child.textContent.trim();
				// remove identified row
				var rowid	= 'Row' + rownum;
				var row		= document.getElementById(rowid);
				var section	= row.parentNode;
				section.removeChild(row);
			}		// have at least 1 rownum element
		}		// have at least 1 parms element
    }			// xmlDoc is defined
}		// function gotDelete

/************************************************************************
 *  function noDelete													*
 *																		*
 *  This method is called if there is no delete registration script.	*
 ************************************************************************/
function noDelete()
{
    alert("CountyMarriagesEdit.js: noDelete: " +
			"script 'deleteCountyMarriagesXml.php' not found on server");
}		// function noDelete

/************************************************************************
 *  function checkFlagBG												*
 *																		*
 *  Validate the current value of a field containing a flag.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkFlagBG()
{
    var	elt		= this;
    var	re		= /^[BGbg ]?$/;
    var	flag		= elt.value;
    var	className	= elt.className;
    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the flag
		if (re.test(flag))
		    elt.className	= className.substring(0, className.length - 5);
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add flag to class name
		if (!re.test(flag))
		    elt.className	= elt.className + "error";
    }		// error not currently flagged
}		// function checkFlagBG

/************************************************************************
 *  function checkFlagBL												*
 *																		*
 *  Validate the current value of a field containing a flag.			*
 *																		*
 *  Input:																*
 *		this		an instance of an HTML input element. 				*
 ************************************************************************/
function checkFlagBL()
{
    var	elt		    = this;
    var	re		    = /^[BLbl ]?$/;
    var	flag		= elt.value;
    var	className	= elt.className;
    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the flag
		if (re.test(flag))
		    elt.className	= className.substring(0, className.length - 5);
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add flag to class name
		if (!re.test(flag))
		    elt.className	= elt.className + "error";
    }		// error not currently flagged
}		// function checkFlagBL

/************************************************************************
 *  function tableKeyDown												*
 *																		*
 *  Handle key strokes in text input fields in a row.					*
 *																		*
 *  Parameters:															*
 *      this    input element                                           *
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function tableKeyDown(e)
{
    if (!e)
    {		                    // browser is not W3C compliant
    	e	            =  window.event;	// IE
    }		                    // browser is not W3C compliant
    var	code		    = e.key;
    var	element		    = e.target;
    var	form		    = element.form;

    // hide the help balloon on any keystroke
    if (helpDiv)
    {		                    // helpDiv currently displayed
		helpDiv.style.display	= 'none';
		helpDiv			= null;	// no longer displayed
    }		                    // helpDiv currently displayed
    clearTimeout(helpDelayTimer);	// clear pending help display
    helpDelayTimer		= null;

    // take action based upon code
    switch (code)
    {
		case "F1":		        // F1
		{
		    displayHelp(this);	// display help page
		    return false;		// suppress default action
		}			            // F1

		case "Enter":
		{			            // enter key
		    if (element)
		    {
				var	cell    	= element.parentNode;
				var	row	        = cell.parentNode;
				var	body	    = row.parentNode;
				var	rownum	    = row.sectionRowIndex;
				if (rownum < (body.rows.length - 1))
				{		        // not the last row
				    rownum++;
				    row			    = body.rows[rownum];
				    var focusSet	= false;
				    var itemNo		= 0;
				    var names	    = '';
				    for(var itd = 0; itd < row.cells.length; itd++)
				    {		    // loop through <td>s
						cell		= row.cells[itd];
						var	children	= cell.children;
						for(var ic = 0; ic < children.length; ic++)
						{	    // loop through children of cell
						    var child	= children[ic];
						    if (child.nodeName.toLowerCase() == 'input' &&
							child.type == 'text')
						    {	// <input type='text'>
								if (!child.readOnly)
								{
								    child.focus();
								    focusSet	= true;
								}
								break;
							}	// first <input type='text'>
						}	    // loop through children of cell
						if (focusSet)
						    break
				    }		    // loop through <td>
				}		        // not the last row
				else
				{
				    var itemNo		= 0;
				    for(var itd = 0; itd < row.cells.length; itd++)
				    {		    // loop through <td>s
				    	cell		    = row.cells[itd];
				    	var	children	= cell.children;
				    	for(var ic = 0; ic < children.length; ic++)
				    	{	    // loop through children of cell
				    	    var child	= children[ic];
				    	    if (child.nodeName.toLowerCase() == 'input' &&
				    		child.type == 'text')
				    	    {	// <input type='text'>
				    	    	if (child.name.substring(0,6) == 'ItemNo')
				    	    	{
				    	    	    itemNo	= child.value;
				    	    	    break;
				    	    	}
					        }	// first <input type='text'>
					    }	    // loop through children of cell
				    }		    // loop through <td>
				    var rowa	= rownum + 2;
				    if (rowa.length == 1)
					    rowa	= '0' + rowa;
				    var rowb	= rownum + 3;
				    if (rowb.length == 1)
					rowb	    = '0' + rowb;
				    var parms	= {'rowa'	: rowa,
					        	   'rowb'	: rowb,
					        	   'itemNo'	: (itemNo-0) + 1};

				    // add new row for groom
				    var template	= document.getElementById('Row$rowa');
				    var	newRow		= createFromTemplate(template,
						                			     parms,
							                		     null);
				    newrow	= body.appendChild(newRow);
				    var inputs		= newRow.getElementsByTagName('input');
				    for (var ii = 0; ii < inputs.length; ii++)
				    {
					    var element	= inputs[ii];
					    initElement(element);
				    }

				    // add new row for bride
				    template	= document.getElementById('Row$rowb');
				    newRow		= createFromTemplate(template,
				            					     parms,
						            			     null);
				    newRow		= body.appendChild(newRow);
				    inputs		= newRow.getElementsByTagName('input');
				    for (var ii = 0; ii < inputs.length; ii++)
				    {
					    var element	= inputs[ii];
					    initElement(element);
				    }
				}
		    }
		    else
				alert("commonMarriage.js: tableKeyDown: element is null.");
		    return false;		// suppress default action
		}			    // enter key

		case "ArrowUp":
		{			    // arrow up key
		    if (element)
		    {
				var	cell	= element.parentNode;
				var	row	    = cell.parentNode;
				var	body	= row.parentNode;
				var	rownum	= row.sectionRowIndex;
				if (rownum > 0)
				{		    // not the first row
				    rownum--;
				    row		= body.rows[rownum];
				    cell	= row.cells[cell.cellIndex];
				    var	children= cell.children;
				    for(var ic = 0; ic < children.length; ic++)
				    {		// loop through children of cell
						var child	= children[ic];
						if (child.nodeName.toLowerCase() == 'input' &&
						    child.type == 'text')
						{	// first <input type='text'>
						    child.focus();
						    break;
						}	// first <input type='text'>
				    }		// loop through children of cell
				}		    // not the first row
		    }
		    else
				alert("commonMarriage.js: tableKeyDown: element is null.");
		    return false;	// suppress default action
		}			        // arrow up key

		case "ArrowDown":
		{			        // arrow down key
		    if (element)
		    {
				var	cell	= element.parentNode;
				var	row	    = cell.parentNode;
				var	body	= row.parentNode;
				var	rownum	= row.sectionRowIndex;
				if (rownum < (body.rows.length - 1))
				{		    // not the last row
				    rownum++;
				    row		= body.rows[rownum];
				    cell	= row.cells[cell.cellIndex];
				    var	children= cell.children;
				    for(var ic = 0; ic < children.length; ic++)
				    {		// loop through children of cell
						var child	= children[ic];
						if (child.nodeName.toLowerCase() == 'input' &&
						    child.type == 'text')
						{	// first <input type='text'>
						    child.focus();
						    break;
					    }	// first <input type='text'>
				    }		// loop through children of cell
				}		    // not the last row
		    }
		    else
				alert("commonMarriage.js: tableKeyDown: element is null.");
		    return false;	// suppress default action
		}			        // arrow down key
    }	                    // switch on key code

    return;
}		// function tableKeyDown

/************************************************************************
 *  function initElement												*
 *																		*
 *  Initialize a form element.                                          *
 *																		*
 *  Parameters:															*
 *		element     instance of HTMLElement                             *
 ************************************************************************/
function initElement(element)
{
    element.onkeydown	= keyDown;          // default event handler

    var namePattern		= /^([a-zA-Z_]+)(\d*)$/;
    var	id			    = element.id;
    if (id.length == 0)
		id			    = element.name;
    var rresult			= namePattern.exec(id);
    var	column			= id;
    var	rownum			= '';
    if (rresult !== null)
    {
		column			= rresult[1];
		rownum			= rresult[2];
    }

    switch(column.toLowerCase())
    {
		case 'domain':
		{
		    element.onkeydown	= keyDown;	// special key handling
		    element.onchange	= change;	// default handler
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}

		case 'volume':
		case 'reportno':
		{	// numeric fields
		    element.onkeydown	= keyDown;	// special key handling
		    element.onchange	= change;	// default handler
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}

		case 'itemno':
		{	// numeric field
		    element.onkeydown	= tableKeyDown;	
		    element.onchange	= change;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}

		case 'role':
		{
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= change;
		    element.checkfunc	= checkFlagBG;
		    element.checkfunc();
		    break;
		}

		case 'givennames':
		case 'fathername':
		case 'mothername':
		case 'witnessname':
		{
		    element.abbrTbl	= GivnAbbrs;
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// given names field

		case 'surname':
		{
		    element.abbrTbl	= SurnAbbrs;
		    element.onchange	= change;
		    element.onkeydown	= tableKeyDown;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// surname field

		case 'age':
		{
		    element.onchange	= change;
		    element.onkeydown	= tableKeyDown;
		    element.checkfunc	= checkAge;
		    element.checkfunc();
		    break;
		}	// age field

		case 'residence':
		case 'birthplace':
		{
		    element.abbrTbl	= LocAbbrs;
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= change;
		    element.checkfunc	= checkAddress;
		    element.checkfunc(); 
		    break;
		}	// location fields

		case 'date':
		{
		    element.abbrTbl	= MonthAbbrs;
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= marriageDateChanged;
		    element.checkfunc	= checkDate;
		    element.checkfunc();
		    break;
		}	// date field

		case 'licensetype':
		{
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= licenseTypeChanged;
		    element.checkfunc	= checkFlagBL;
		    element.checkfunc();
		    break;
		}

		case 'witnessname':
		{
		    element.abbrTbl		= GivnAbbrs;
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// witness names field

		case 'remarks':
		{
		    element.onkeydown	= tableKeyDown;
		    element.onchange	= change;
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}

		case 'link':
		{
		    element.onclick	= linkToTree;
		    break;
		}

		case 'clear':
		{
		    element.onclick	= clearFromTree;
		    break;
		}

		case 'details':
		{
		    element.onclick	= showDetails;
		    break;
		}

		case 'delete':
		{
		    element.onclick	= deleteRow;
		    break;
		}

    }			// act on column name
}		// function initElement

/************************************************************************
 *  function marriageDateChanged										*
 *																		*
 *  Take action when the user changes the marriage date field			*
 *																		*
 *  Input:																*
 *		this		an instance of an HTML input element. 				*
 ************************************************************************/
function marriageDateChanged()
{
    var	form		= this.form;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    var	value		= this.value;
    value		    = value.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    this.value		= value.replace(/(\d)([a-zA-Z])/g,"$1 $2");

    changeElt(this);	// change case and expand abbreviations

    if (this.checkfunc)
		this.checkfunc();

    var	rownum		= this.name.substring(4);
    var roleElement	= form.elements['Role' + rownum];
    if (roleElement && roleElement.value.toUpperCase() == 'G')
    {
		var brownum	= (rownum - 0) + 1;
		if (brownum < 10)
		{
		    brownum	= "0" + brownum;
		}
		var brideDateName	= 'Date' + brownum;
		var brideDateElement	= form.elements[brideDateName];
		if (brideDateElement)
		    brideDateElement.value	= this.value;
		else
		    alert("Unable to find element with name='" + brideDateName + "'");
    }
}		// function marriageDateChanged

/************************************************************************
 *  function licenseTypeChanged											*
 *																		*
 *  Take action when the user changes the licence type field			*
 *																		*
 *  Input:																*
 *		this		an instance of an HTML input element. 				*
 ************************************************************************/
function licenseTypeChanged()
{
    var	form		= this.form;
    changeElt(this);	// change case and expand abbreviations

    if (this.checkfunc)
		this.checkfunc();

    var	rownum		= this.name.substring(11);
    var roleName	= 'Role' + rownum;
    var roleElement	= form.elements[roleName];
    if (roleElement && roleElement.value.toUpperCase() == 'G')
    {
		var brownum	= (rownum - 0) + 1;
		if (brownum < 10)
		{
		    brownum	= "0" + brownum;
		}
		var brideLtName		= 'LicenseType' + brownum;
		var brideLtElement	= form.elements[brideLtName];
		if (brideLtElement)
		    brideLtElement.value	= this.value;
		else
		    alert("Unable to find element with name='" + brideLtName + "'");
    }
}		// function licenseTypeChanged




