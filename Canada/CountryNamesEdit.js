/************************************************************************
 *  CountryNamesEdit.js							*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  CountryNamesEdit.php						*
 *									*
 *  History:								*
 *	2017/10/27	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize the dynamic functionality once the page is loaded	*
 ************************************************************************/
function onLoad()
{
    pageInit();

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
    
	    if (element.id == 'Add')
	    {
		element.onclick	= addName;
	    }
	    else
	    if (element.name.substring(0, 4) == 'Code')
	    {
		element.helpDiv	= 'Code';
	    }
	    else
	    if (element.name.substring(0, 4) == 'Name')
	    {
		element.helpDiv	= 'Name';
		element.change	= change;
	    }
	    else
	    if (element.name.substring(0, 7) == 'Article')
	    {
		element.helpDiv	= 'Article';
		element.change	= change;
	    }
	    else
	    if (element.name.substring(0, 10) == 'Possessive')
	    {
		element.helpDiv	= 'Possessive';
		element.change	= change;
	    }
	    else
	    if (element.id.substring(0, 6) == 'Delete')
	    {
		element.helpDiv	= 'Delete';
		element.onclick	= deleteName;
	    }
	}	// loop through all elements in the form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  changeCode								*
 *									*
 *  Take action when the user changes the country code.			*
 *									*
 *  Input:								*
 *	$this		instance of <input name='Code...'>		*
 ************************************************************************/
function changeCode()
{
    changeElt(this);
    var	oldCode	= this.name.substring(4);
    var newCode	= this.value;
    var	form	= this.form;
    this.name					= 'Code' + newCode;
    if (form.elements['Code' + newCode])
    {		// successfully renamed the current element
	 alert("changeCode: oldCode='" + oldCode + "', newCode='" + newCode + "'" +
		tagToString(this.parentNode.parentNode));
	form.elements['Name' + oldCode].name		= 'Name' + newCode;
	form.elements['StartYear' + oldCode].name	= 'StartYear' + newCode;
	form.elements['EndYear' + oldCode].name		= 'EndYear' + newCode;
	var deleteBtn	= document.getElementById('Delete' + oldCode);
	deleteBtn.id					= 'Delete' + newCode;
	deleteBtn.onclick				= deleteName;
	deleteBtn.disabled				= false;
	var domainBtn	= document.getElementById('EditDomains' + oldCode);
	domainBtn.id			= 'EditDomains' + newCode;
	domainBtn.onclick				= showDomains;
	domainBtn.disabled				= false;
	var addBtn	= document.getElementById('Add');
	addBtn.disabled					= false;
    }		// successfully renamed the current element
    else
    {		// unable to rename, probably some back level of IE!
	alert('Unable to rename element Code' + oldCode + ' to Code' + newCode);
    }		// unable to rename, probably some back level of IE!
}		// changeCode

/************************************************************************
 *  deleteName								*
 *									*
 *  When a Delete button is clicked this function removes the		*
 *  row from the table.							*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Delete....'		*
 ************************************************************************/
function deleteName()
{
    var	code	= this.id.substring(6);	// language code
    var	form	= this.form;
    var	cell	= this.parentNode;	// <td> containing button
    var	row	= cell.parentNode;	// <tr> containing button
    var inputs	= row.getElementsByTagName('input');
    for(var ii = 0; ii < inputs.length; ii++)
    {
	child	= inputs[ii];
	if (child.name.substring(0,4) == 'Name')
	{
	    child.setAttribute('value', '');
	}
	child.type		= 'hidden';
    }
    row.removeChild(cell);
    return false;
}		// deleteName

/************************************************************************
 *  addName								*
 *									*
 *  When the Add country button is clicked this function adds a row	*
 *  into the table.							*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Add'>			*
 ************************************************************************/
function addName()
{
    this.disabled	= true;	// only permit one row to be added
    var	form		= this.form;
    var	parms	= {"code"	: "XX"};
    var	template	= document.getElementById("Row$code");
    var	newRow		= createFromTemplate(template,
					     parms,
					     null);
    var	table		= document.getElementById("dataTbl");
    var	tbody		= table.tBodies[0];
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added country
    var	codeElt		= form.CodeXxx;
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= changeCode;
				     
    return false;
}		// addName
