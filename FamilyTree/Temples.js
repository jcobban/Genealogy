/************************************************************************
 *  Temples.js								*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page Temples.php.							*
 *									*
 *  History:								*
 *	2012/12/06	created						*
 *	2013/05/29	use actMouseOverHelp common function		*
 *	2013/08/01	defer facebook initialization until after load	*
 *									*
 *  Copyright &copy; 2013 James A. Cobban				*
 ************************************************************************/

window.onload	= onloadTemples;

/************************************************************************
 *  onLoadTemples							*
 *									*
 *  Initialize dynamic functionality of page.				*
 ************************************************************************/
function onloadTemples()
{
    pageInit();

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
	var form	= document.forms[i];

	if (form.name == 'locForm')
	{	// locForm
	    form.onsubmit	= validateForm;
	    form.onreset 	= resetForm;
	}	// locForm

	for(var j = 0; j < form.elements.length; j++)
	{	// loop through all elements
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    // default actions
	    element.onkeydown	= keyDown;
	    element.onchange	= change;	// default handler
	}	// loop through elements in form
    }		// iterate through all forms

    // add mouseover actions for forward and backward links
    var npprev	= document.getElementById('npprev');
    if (npprev)
    {		// defined
	npprev.onmouseover	= linkMouseOver;
	npprev.onmouseout	= linkMouseOut;
    }		// defined
    var npnext	= document.getElementById('npnext');
    if (npnext)
    {		// defined
	npnext.onmouseover	= linkMouseOver;
	npnext.onmouseout	= linkMouseOut;
    }		// defined
}		// onLoadDeath

/************************************************************************
 *  validateForm							*
 *									*
 *  Ensure that the data entered by the user has been minimally		*
 *  validated before submitting the form.				*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  linkMouseOver							*
 *									*
 *  This function is called if the mouse moves over a forward or	*
 *  backward hyperlink on the invoking page.				*
 *									*
 *  Parameters:								*
 *	this		element the mouse moved on to			*
 ************************************************************************/
function linkMouseOver()
{
    var	msgDiv	= document.getElementById('mouse' + this.id);
    if (msgDiv)
    {		// support for dynamic display of messages
	// display the messages balloon in an appropriate place on the page
	var leftOffset		= getOffsetLeft(this);
	if (leftOffset > 500)
	    leftOffset	-= 200;
	msgDiv.style.left	= leftOffset + "px";
	msgDiv.style.top	= (getOffsetTop(this) - 30) + 'px';
	msgDiv.style.display	= 'block';

	// so key strokes will close window
	helpDiv			= msgDiv;
	helpDiv.onkeydown	= keyDown;
    }		// support for dynamic display of messages
}		// linkMouseOver

/************************************************************************
 *  linkMouseOut							*
 *									*
 *  This function is called if the mouse moves off a forward or		*
 *  backward hyperlink on the invoking page.				*
 *									*
 *  Parameters:								*
 *	this		element the mouse moved on to			*
 ************************************************************************/
function linkMouseOut()
{
    if (helpDiv)
    {
	helpDiv.style.display	= 'none';
	helpDiv			= null;
    }
}		// linkMouseOut

