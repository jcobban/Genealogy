/************************************************************************
 *  relationshipCalculator.js											*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page relationshipCalculator.php.									*
 *																		*
 *  History:															*
 *		2015/01/23		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function onLoad()
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

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
 *  function close												*
 *																		*
 *  This method is called when the user clicks on the button to close		*
 *  the dialog.																*
 *																		*
 *  Parameters:																*
 *		this		<button id='Close'>										*
 ************************************************************************/
function close()
{
    closeFrame();
}		// close

