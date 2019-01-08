/************************************************************************
 *  reqReportIndivids.js						*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page reqReportIndivids.php, which implements the ability to produce	*
 *  a simple report of individuals matching specified criteria.		*
 *									*
 *  History:								*
 *	2012/02/08	create						*
 *	2013/05/29	use actMouseOverHelp common function		*
 *	2013/08/01	defer facebook initialization until after load	*
 *	2015/07/28	fix comment blocks				*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

window.onload	= onloadEdit;

/************************************************************************
 * onloadEdit								*
 *									*
 * Initialize elements.							*
 ************************************************************************/
function onloadEdit()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for (var fi = 0; fi < document.forms.length; fi++)
    {
	var form		= document.forms[fi];

	// form wide options
	//form.onsubmit		 	= validateForm;
	//form.onreset 			= resetForm;

	var formElts	= form.elements;
	for (var ei = 0; ei < formElts.length; ++ei)
	{	// loop through all elements in form
	    var element	= formElts[ei];

	    element.onkeydown	= keyDown;	// default keystroke handler
	    element.onchange	= change;	// default handler

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    // identify the element by name
	    var	name	= element.name;
	    if (name === undefined || name.length == 0)
		name	= element.id;

	    switch(name)
	    {	// act on specific elements
		case 'chooseFields':
		{
		    element.onchange	= chooseField;
		    break;
		}	// chooseFields

		case 'chooseSort':
		{
		    element.onchange	= chooseSort;
		    break;
		}	// chooseSort
	    }	// act on specific elements
	}	// loop through all elements in a form
    }		// loop through all forms in the document
}		// loadEdit

/************************************************************************
 *  chooseField								*
 *									*
 *  This method is called if the user changes the active selection in	*
 *  the <select id='chooseFields'>. The selected item is moved to the	*
 *  <select name='fields'> list.					*
 *									*
 *  Parameters:								*
 *	this	<select id='chooseFields'>				*
 ************************************************************************/
function chooseField()
{
    var	select	= this;
    var option	= select.options[select.selectedIndex];
    if (option && option.value != '0')
    {
	select.removeChild(option);
	select.selectedIndex	= 0;
	var	form	= document.getElementById('reqForm');
	var	target	= form.elements['fields[]'];
	target.appendChild(option);
	option.selected	= true;
    }
}		// chooseFields

/************************************************************************
 *  chooseSort								*
 *									*
 *  This method is called if the user changes the active selection in	*
 *  the <select id='chooseSort'>. The selected item is moved to the	*
 *  <select name='orderby'> list.					*
 *									*
 *  Parameters:								*
 *	this	<select id='chooseSort'>				*
 ************************************************************************/
function chooseSort()
{
    var	select	= this;
    var option	= select.options[select.selectedIndex];
    if (option && option.value != '0')
    {
	select.removeChild(option);
	select.selectedIndex	= 0;
	var	form	= document.getElementById('reqForm');
	var	target	= form.elements['orderby[]'];
	target.appendChild(option);
	option.selected	= true;
    }
}		// chooseSort
