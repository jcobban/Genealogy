/************************************************************************
 *  editCountries.js							*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  editCountries.php							*
 *									*
 *  History:								*
 *	2017/02/04	created						*
 *	2017/08/13	correct add country support			*
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

	    var	name	= element.name;
	    if (name.length == 0)
		name	= element.id;
	    var	column	= name.substring(0, name.length - 2).toLowerCase();
	    var	code	= name.substring(name.length - 2).toUpperCase();

	    switch (column)
	    {		// act on a field from a table row
		case 'code':
		{	// ISO country code
		    element.helpDiv	= 'Code';
		    break;
		}	// ISO country code

		case 'name':
		{	// Name in English
		    element.helpDiv	= 'Name';
		    element.change	= change;
		    break;
		}	// Name in English

		case 'names':
		{	// names in other languages
		    element.helpDiv	= 'Names';
		    element.onclick	= showNames;
		    break;
		}	// names in other languages

		case 'editdomains':
		{	// edit states/provinces of the country
		    element.helpDiv	= 'EditDomains';
		    element.onclick	= showDomains;
		    break;
		}	// edit states/provinces of the country

		case 'delete':
		{	// delete this country
		    element.helpDiv	= 'Delete';
		    element.onclick	= deleteCountry;
		    break;
		}	// delete this country

		default:
		{
		    if (element.id == 'Add')
		    {
			element.onclick	= addCountry;
		    }
		}
	    }			// act on a field
	}			// loop through all elements in the form
    }				// loop through all forms
}		// function onLoad

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
	// alert("changeCode: oldCode='" + oldCode + "', newCode='" + newCode + "'" +
	//	tagToString(this.parentNode.parentNode));
	form.elements['Name' + oldCode].name		= 'Name' + newCode;
	form.elements['StartYear' + oldCode].name	= 'StartYear' + newCode;
	form.elements['EndYear' + oldCode].name		= 'EndYear' + newCode;
	var deleteBtn	= document.getElementById('Delete' + oldCode);
	deleteBtn.id					= 'Delete' + newCode;
	deleteBtn.onclick				= deleteCountry;
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
 *  deleteCountry							*
 *									*
 *  When a Delete button is clicked this function removes the		*
 *  row from the table.							*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Delete....'		*
 ************************************************************************/
function deleteCountry()
{
    var	cc	= this.id.substring(6);
    var	form	= this.form;
    var	cell	= this.parentNode;
    var	row	= cell.parentNode;
    var	section	= row.parentNode;
    section.removeChild(row);
    var	operator	= document.createElement('input');
    operator.type	= 'hidden';
    operator.name	= 'deleteCountry' + cc;
    operator.value	= 'deleteCountry' + cc;
    form.appendChild(operator);

    return false;
}		// deleteCountry

/************************************************************************
 *  showNames								*
 *									*
 *  When a Names button is clicked this function displays the		*
 *  edit dialog for the list of other names of a country.		*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Names....'		*
 ************************************************************************/
function showNames()
{
    var	form		= this.form;
    var	cc		= this.id.substring(this.id.length - 2);
    var	lang		= 'en';

    if (form.lang)
	lang		= form.lang.value;
    else 
    if ('lang' in args)
	lang		= args['lang'];

    window.open('CountryNamesEdit.php?cc=' + cc + '&lang=' + lang,
		'_blank');
    return false;
}		// showNames

/************************************************************************
 *  showDomains								*
 *									*
 *  When a Domains button is clicked this function displays the		*
 *  edit dialog for the list of domains in a country.			*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Edit....'		*
 ************************************************************************/
function showDomains()
{
    var	form		= this.form;
    var	cc		= this.id.substring(this.id.length - 2);
    var	lang		= 'en';

    if (form.lang)
	lang		= form.lang.value;
    else 
    if ('lang' in args)
	lang		= args['lang'];

    window.open('DomainsEdit.php?cc=' + cc + '&lang=' + lang,
		'_blank');
    return false;
}		// showDomains

/************************************************************************
 *  addCountry								*
 *									*
 *  When the Add country button is clicked this function adds a row	*
 *  into the table.							*
 *									*
 *  Input:								*
 *	$this		<button type=button id='Add'>			*
 ************************************************************************/
function addCountry()
{
    this.disabled	= true;	// only permit one row to be added
    var	form		= this.form;
    var	parms	= {"country"	: "XX"};
    var	template	= document.getElementById("Row$country");
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
}		// addCountry
