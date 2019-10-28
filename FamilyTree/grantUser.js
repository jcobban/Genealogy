/************************************************************************
 *  grantUser.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page grantUser.php.											    	*
 *																		*
 *  History:															*
 *		2019/08/09		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	    = onLoadGrant;

/************************************************************************
 *  function onLoadGrant												*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onLoadGrant()
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    var text    = '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {			            // loop through all forms
		var	form		= document.forms[fi];
		for(var j = 0; j < form.elements.length; j++)
		{		            // loop through all elements in the form
		    var elt	        = form.elements[j];

            var name        = elt.name;
            if (typeof name == 'undefined' || name.length == 0)
                name        = elt.id;

            text            += form.name + '.' + name + ',';
            switch(name.toLowerCase())
            {
                case 'close':
                {
                    elt.addEventListener('click', closeMe);
                    break;
                }
            }
		}		            // loop through all elements in the form
    }		                // loop through all forms

}		// function onLoadGrant

/************************************************************************
 *  function closeMe            								        *
 *																		*
 *  This method is called when the user clicks on the Close button      *
 *																		*
 *	Input:																*
 *		this			<button id="Close">	    						*
 *		ev              W3C compliant browsers pass an Event            *
 ************************************************************************/
function closeMe(ev)
{
    ev.stopPropagation();

    closeFrame();
}	    // function closeMe

