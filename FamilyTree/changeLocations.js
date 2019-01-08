/************************************************************************
 *  changeLocations.js							*
 *									*
 *  Default initialization routine executed to initialize javascript	*
 *  support for the page changeLocations.php				*
 *									*
 *  History:								*
 *	2014/09/16	created						*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *									*
 *  Define the function to be called once the web page is loaded.	*
 ************************************************************************/
    window.onload	= defaultOnLoad;

/************************************************************************
 *  defaultOnLoad							*
 *									*
 *  Perform initialization functions once the page is loaded.		*
 *  Each field is enabled for the default keyboard and mouse support.	*
 ************************************************************************/
function defaultOnLoad()
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
	}	// loop through elements in form
    }		// iterate through all forms
}		// defaultOnLoad
