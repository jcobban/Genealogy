/************************************************************************
 *  editCountries.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  editCountries.php													*
 *																		*
 *  History:															*
 *		2017/02/04		created											*
 *		2017/08/13		correct add country support						*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04/07      ensure that the paging lines can be displayed   *
 *		                within the visible portion of the browser.      *
 *		2019/07/22      open sub-forms in right half of window          *
 *		2021/01/13      document that Event passed to all handlers      *
 *		                remove support for ES5                          *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *																		*
 *  Input:																*
 *		this		instance of window                                  *
 *		ev          instance of 'load' Event                            *
 ************************************************************************/
function onLoad(ev)
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    let	element;
    let trace	                = '';
    for (let fi = 0; fi < document.forms.length; fi++)
    {		            // loop through all forms
		let form	            = document.forms[fi];
		trace	                += "<form ";
		if (form.name.length > 0)
		    trace	            += "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	            += "id='" + form.id + "' ";
		trace	                += ">";

		for (let i = 0; i < form.elements.length; ++i)
		{	            // loop through all elements of form
		    element		        = form.elements[i];
		    trace               += "<" + element.nodeName + " ";
		    if (element.name.length > 0)
				trace	        += "name='" + element.name + "' ";
		    if (element.id.length > 0)
				trace	        += "id='" + element.id + "' ";
		    trace	            += ">";
		    element.onkeydown	= keyDown;  // common

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	        // set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	        // set mouseover on containing cell
		    else
		    {	        // set mouseover on input element itself
				element.onmouseover		        = eltMouseOver;
				element.onmouseout		        = eltMouseOut;
		    }	        // set mouseover on input element itself

		    let	name	= element.name;
		    if (name.length == 0)
				name	= element.id;
		    let	column	= name.substring(0, name.length - 2).toLowerCase();
		    let	code	= name.substring(name.length - 2).toUpperCase();

		    switch (column)
		    {		    // act on a field from a table row
				case 'code':
				{	    // ISO country code
				    element.helpDiv			= 'Code';
				    break;
				}	    // ISO country code

				case 'name':
				{	    // Name in English
				    element.helpDiv			= 'Name';
				    element.change			= change;
				    break;
				}	    // Name in English

				case 'names':
				{	    // names in other languages
				    element.helpDiv			= 'Names';
				    element.onclick			= showNames;
				    break;
				}	    // names in other languages

				case 'editdomains':
				{	    // edit states/provinces of the country
				    element.helpDiv			= 'EditDomains';
				    element.onclick			= showDomains;
				    break;
				}	    // edit states/provinces of the country

				case 'delete':
				{	    // delete this country
				    element.helpDiv			= 'Delete';
				    element.onclick			= deleteCountry;
				    break;
				}	    // delete this country

                case 'a':
				{
				    if (code == 'DD')
				    {
						element.onclick	    = addCountry;
				    }
				    break;
				}
		    }			// act on a field
		}			    // loop through all elements in the form
    }				    // loop through all forms

}		// function onLoad

/************************************************************************
 *  function changeCode													*
 *																		*
 *  Take action when the user changes the country code.					*
 *																		*
 *  Input:																*
 *		this		instance of <input name='Code...'>				    *
 *		ev          instance of 'change' Event                          *
 ************************************************************************/
function changeCode(ev)
{
    changeElt(this);        // common functionality
    let	oldCode	                = this.name.substring(4);
    let newCode	                = this.value;
    if (/^[a-zA-Z]{2}$/.test(newCode))
        newCode                 = newCode.toUpperCase();
    else
    {
        let text        = document.getElementById('ccInvalid').innerHTML;
        alert(text.replace('$newCode', newCode));
        return;
    }
    let	form	                = this.form;
    this.id					    = 'Code' + newCode;
    this.name			        = 'Code' + newCode;
	let nameInput	= form.elements['Name' + oldCode];
	nameInput.id					= 'Name' + newCode;
	nameInput.name					= 'Name' + newCode;
	let deleteBtn	= document.getElementById('Delete' + oldCode);
	deleteBtn.id					= 'Delete' + newCode;
	deleteBtn.onclick				= deleteCountry;
	deleteBtn.disabled				= false;
	let domainBtn	= document.getElementById('EditDomains' + oldCode);
	domainBtn.id			        = 'EditDomains' + newCode;
	domainBtn.onclick				= showDomains;
	domainBtn.disabled				= false;
	let addBtn	    = document.getElementById('Add');
		addBtn.disabled					= false;
}		// function changeCode

/************************************************************************
 *  function deleteCountry												*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.  When the form is submitted the associated      *
 *  instance of Country is removed from the Countries table.            *
 *																		*
 *  Input:																*
 *		this			<button type=button id='Delete....'				*
 *		ev              instance of 'click' event                       *
 ************************************************************************/
function deleteCountry(ev)
{
    let	cc	        	= this.id.substring(6);
    let	form	    	= this.form;
    let	cell	    	= this.parentNode;
    let	row	        	= cell.parentNode;
    let	section	    	= row.parentNode;
    section.removeChild(row);
    let	operator		= document.createElement('input');
    operator.type		= 'hidden';
    operator.name		= 'deleteCountry' + cc;
    operator.value		= 'deleteCountry' + cc;
    form.appendChild(operator);

    return false;
}		// function deleteCountry

/************************************************************************
 *  function showNames													*
 *																		*
 *  When a Names button is clicked this function displays the			*
 *  edit dialog for the list of other names of a country.				*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Names....'				    *
 *		ev          instance of 'click' Event                           *
 ************************************************************************/
function showNames(ev)
{
    let	form		= this.form;
    let	cc		    = this.id.substring(this.id.length - 2);
    let	lang		= 'en';

    if (form.lang)
		lang		= form.lang.value;
    else 
    if ('lang' in args)
		lang		= args['lang'];

    openFrame("source",
              'CountryNamesEdit.php?cc=' + cc + '&lang=' + lang,
		      "right");
    return false;
}		// function showNames

/************************************************************************
 *  function showDomains												*
 *																		*
 *  When a Domains button is clicked this function displays the			*
 *  edit dialog for the list of domains in a country.					*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Edit....'			        *
 *		ev          instance of 'click' Event                           *
 ************************************************************************/
function showDomains(ev)
{
    let	form		    = this.form;
    let	cc		        = this.id.substring(this.id.length - 2);
    let	lang		    = 'en';

    if (form.lang)
		lang		    = form.lang.value;
    else 
    if ('lang' in args)
		lang		    = args['lang'];

    openFrame("source",
              'DomainsEdit.php?cc=' + cc + '&lang=' + lang,
		      "right");
    return false;
}		// function showDomains

/************************************************************************
 *  function addCountry													*
 *																		*
 *  When the Add country button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Add'>						*
 *		ev          instance of 'click' Event                           *
 ************************************************************************/
function addCountry(ev)
{
    this.disabled   	= true;	// only permit one row to be added
    let	form		    = this.form;
    let	parms	        = {"country"	: "XX"};
    let	template	    = document.getElementById("Row$country");
    let	newRow		    = createFromTemplate(template,
						    			     parms,
							    		     null);
    let	table		    = document.getElementById("dataTable");
    let	tbody		    = table.tBodies[0];
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added country
    let	codeElt		    = form.CodeXX;
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= changeCode;

    return false;
}		// function addCountry
