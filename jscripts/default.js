/************************************************************************
 *  default.js								*
 *									*
 *  Default initialization routine executed to initialize javascript	*
 *  support for web pages and PHP scripts that have no specific		*
 *  requirements.							*
 *									*
 *  History:								*
 *	2012/01/24	split out of util.js				*
 *	2013/06/01	use actMouseOverHelp function			*
 *			rightTop button is now in a form, so does not	*
 *			require special handling			*
 *	2013/07/30	defer facebook initialization until after load	*
 *	2014/09/16	pretty up the comments				*
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
