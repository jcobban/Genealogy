/************************************************************************
 *  Addresses.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page Addresses.php.							*
 *									*
 *	2012/01/13	change class names				*
 *	2013/02/23	move setting onload function here		*
 *	2013/05/29	use actMouseOverHelp common function		*
 *			standardize initialization			*
 *	2013/07/31	defer setup of facebook link			*
 *	2017/08/04	class LegacyAddress renamed to Address		*
 *	2018/02/12	add support for passing language to scripts	*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/

window.onload	= initAddresses;

/************************************************************************
 *  onLoadAddresses							*
 *									*
 *  Initialize elements.						*
 ************************************************************************/
function initAddresses()
{
    pageInit();

    // activate dynamic functionality for elements
    for (var fi = 0; fi < document.forms.length; fi++)
    {
	var	form		= document.forms[fi];

	if (form.name == 'locForm')
	{		// main form
	    // set action methods for elements
	    form.onsubmit		 	= validateForm;
	    form.onreset 			= resetForm;
	}		// main form

        // activate handling of key strokes in text input fields
        // including support for context specific help
        var formElts	= form.elements;
        for (var i = 0; i < formElts.length; ++i)
        {			// loop through all elements in the form
	    var element		= formElts[i];
    
	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);
    
	    element.onkeydown	= keyDown;
	    element.onchange	= change;	// default handler
	    
	    var name	= element.name;
	    if (name === undefined || name.length == 0)
	        name	= element.id;
	    var	idar	= '';
	    var	results	= /^([a-zA-Z]+)(\d+)$/.exec(name);
	    if (results)
	    {
		name	= results[1];
		idar	= results[2];
	    }
	    
	    switch(name.toLowerCase())
	    {		// act on specific elements
	        case 'add':
	        {
		    element.onclick	= addAddress;
		    break;
	        }	// add an address

		case 'delete':
	        {
		    element.onclick	= delAddress;
		    break;
	        }	// add an address

	    }		// act on specific elements
        }		// loop through all elements in the form
    }			// loop through all forms in the document
}		// function onLoadAddresses

/************************************************************************
 *  validateForm							*
 *									*
 *  Ensure that the data entered by the user has been minimally		*
 *  validated before submitting the form.				*
 ************************************************************************/
function validateForm()
{
    return true;
}		// function validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 *									*
 *  Input:      							*
 *	this		<button id="Reset">       			*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  addAddress								*
 *									*
 *  This method is called when the user requests to add a new address.	*
 *									*
 *  Input:      							*
 *	this		<button id="Add">       			*
 ************************************************************************/
function addAddress()
{
    var	lang		= 'en';
    if ('lang' in args)
	lang		= args['lang'];
    location		= 'Address.php?idar=0&kind=2&lang=' + lang;
    return false;
}	// function addAddress

/************************************************************************
 *  delAddress								*
 *	    								*
 *  This method is called when the user requests to delete an address.	*
 *									*
 *  Input:      							*
 *	this		<button id="Delete9999">       			*
 ************************************************************************/
function delAddress()
{
    var	form		= this.form;
    var	idar		= this.id.substring(6);
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    row.style.display	= 'none';
    var	actionTag	= form.elements['action' + idar];
    actionTag.value	= 'delete';
    this.disabled	= true;
    
    return false;
}	// function delAddress
