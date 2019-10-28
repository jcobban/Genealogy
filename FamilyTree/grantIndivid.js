/************************************************************************
 *  grantIndivid.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page grantIndivid.php.												*
 *																		*
 *  History:															*
 *		2010/12/09		created											*
 *		2012/01/13		change class names								*
 *		2013/05/29		add support for mouseover help					*
 *		2013/07/31		defer setup of facebook link					*
 *		2015/01/19		enclose comment blocks							*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/07/12      add support for username pattern                *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoadGrant;

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
		    if (form.name == 'grantForm')
		    {		        // main form
				form.onsubmit	= validateForm;
				form.onreset 	= resetForm;
		    }		        // main form

		    var elt	= form.elements[j];
		    elt.onkeydown	= keyDown;
		    elt.onchange	= change;	// default handler

            var name        = elt.name;
            if (name.length == 0)
                name        = elt.id;

            text            += form.name + '.' + name + ',';
            switch(name.toLowerCase())
            {
                case 'pattern':
                {
                    elt.onchange    = patternChanged;
                    break;
                }
            }
		}		            // loop through all elements in the form
    }		                // loop through all forms

}		// onLoadGrant

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  function patternChanged										        *
 *																		*
 *  This method is called when the user changes the user name pattern.  *
 *																		*
 *	Input:																*
 *		this			<input name="pattern">							*
 ************************************************************************/
function patternChanged()
{
    location    = location.href + "&pattern=" + encodeURI(this.value);
    return false;
}	// function patternChanged

