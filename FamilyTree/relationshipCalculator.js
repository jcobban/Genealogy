/************************************************************************
 *  relationshipCalculator.js						*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page relationshipCalculator.php.					*
 *									*
 *  History:								*
 *	2015/01/23	created						*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

    window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize dynamic functionality of elements.			*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
	var form	= document.forms[i];
	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    var	name	= element.name;
	    if (name === undefined || name.length == 0)
		name	= element.id;

	    // take action specific to the element based on its name
	    switch(name)
	    {		// switch on name
		case "Close":
		{
		    element.onclick	= close;
		    break;
		}	// Close

	    }		// switch on name
	}	// loop through elements in form
    }		// iterate through all forms
}		// onLoad

/************************************************************************
 *  close								*
 *									*
 *  This method is called when the user clicks on the button to close	*
 *  the dialog.								*
 *									*
 *  Parameters:								*
 *	this	<button id='Close'>					*
 ************************************************************************/
function close()
{
    closeFrame();
}		// close

