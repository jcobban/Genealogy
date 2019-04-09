/************************************************************************
 *  editTownships.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  editTownships.php													*
 *																		*
 *  History:															*
 *		2012/05/08		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/10/19		support multiple countries and states/provinces	*
 *						move focuse to ID field of added township		*
 *						copy updated value of ID field to name if empty	*
 *		2016/11/13		add row even if existing table is empty			*
 *		2017/02/07		simplify implementation of delete township		*
 *		2018/10/21      change name of new township dialog template     *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *																		*
 *  Input:																*
 *		this		window												*
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

		    var name		= element.id;
		    if (name == '')
				name		= element.name;
		    var	line		= '';
		    var regexResult	= /^([a-zA-Z_$]+)(\d*)/.exec(name);
		    if (regexResult)
		    {
				name		= regexResult[1];
				line		= regexResult[2];
		    }

		    switch(name.toLowerCase())
		    {			// act on column name
				case 'add':
				{
				    element.onclick	= addTownship;
				    break;
				}

				case 'code':
				{
				    element.helpDiv	= 'Code';
				    element.change	= changeCode;
				    break;
				}

				case 'name':
				{
				    element.helpDiv	= 'Name';
				    element.change	= change;
				    break;
				}

				case 'delete':
				{
				    element.helpDiv	= 'Delete';
				    element.onclick	= deleteTownship;
				    break;
				}

				case 'concessions':
				{
				    element.helpDiv	= 'Concessions';
				    element.onclick	= showConcessions;
				    break;
				}
		    }			// act on column name
		}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  function deleteTownship												*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'>								*
 ************************************************************************/
function deleteTownship()
{
    var	form		    = this.form;
    var	rowid	    	= this.id.substring(6);
    var	townshipCode	= form.elements['Code' + rowid].value;
    var	deleteElement	= document.createElement('input');
    deleteElement.type	= 'hidden';
    deleteElement.name	= 'DeleteCode' + townshipCode;
    deleteElement.value	= 'DeleteCode' + townshipCode;
    var	cell	    	= this.parentNode;
    var	row	        	= cell.parentNode;
    var	section	    	= row.parentNode;
    var	table	    	= section.parentNode;
    form.insertBefore(deleteElement, table);
    section.removeChild(row);
    return false;
}		// deleteTownship

/************************************************************************
 *  function addTownship												*
 *																		*
 *  When the Add county button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		this		<button id='Add...'>								*
 ************************************************************************/
function addTownship()
{
    var	form	    	= this.form;
    var	table	    	= document.getElementById("dataTbl");
    var	tbody	    	= table.tBodies[0];
    var	lastRow	    	= tbody.rows[tbody.rows.length - 1];
    var	numTownships	= tbody.rows.length;
    if (lastRow instanceof Node)
		numTownships	= lastRow.id.substring(3) - 0 + 1;
    var	domain		    = document.townshipForm.Domain.value;
    var	cc	    	    = domain.substring(0,2);
    var	province	    = domain.substring(2,2);
    var	parms	        = {"domain"	: domain,
		        		   "prov"	: province,
		        		   "cc"		: cc,
		        		   "code"	: '',
		        		   "name"	: '',
		        		   "line"	: numTownships};
    var	newRow		    = createFromTemplate("NewTownship$line",
				        				     parms,
				        				     null);

    tbody.appendChild(newRow);

    // initialize dynamic functionality of new row
    var	codeField   	= form.elements["Code" + numTownships];
    var nameField	    = form.elements["Name" + numTownships];
    var	deleteBtn	    = document.getElementById("Delete" + numTownships);
    codeField.onchange	= changeCode;
    codeField.focus();
    nameField.onchange	= change;
    deleteBtn.onclick	= deleteTownship;

    return false;
}		// addTownship

/************************************************************************
 *  function changeCode													*
 *																		*
 *  Take special action when the user changes the identifier field.		*
 *																		*
 *  Input:																*
 *		$this		<input id='Code...'>								*
 ************************************************************************/
function changeCode()
{
    var form	    = this.form;
    var	line		= this.name.substring(4);
    capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);

    var nameField	= form.elements['Name' + line];
    if (nameField && nameField.value == '')
		nameField.value	= this.value;

    if (this.checkfunc)
		this.checkfunc();
}		// changeCode

/************************************************************************
 *  function showConcessions											*
 *																		*
 *  When a Concessions button is clicked this function displays the		*
 *  edit concessions application.										*
 *																		*
 *  Input:																*
 *		this		<button id='Concessions...'>						*
 ************************************************************************/
function showConcessions()
{
    var	line		= this.id.substring(11);
    var form		= this.form;
    var	domain		= form.Domain.value;
    var	county		= form.County.value;
    var	township	= form.elements['Code' + line].value;
    var	url	    	= 'ConcessionsEdit.php?domain=' + domain +
								'&county=' + county +
								'&township=' + township;
    window.open(url,
				'_blank');
    return false;
}		// showConcessions

