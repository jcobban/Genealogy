/**
 *  testDate.js
 *
 *  Initialization routine executed to initialize javascript support
 *  for testDate.php
 *
 *  History:
 *	2013/08/12	created
 *
 *  Copyright &copy; 2013 James A. Cobban
 **/

/**
 *  Initialization code that is executed when this script is loaded.
 *
 *  Define the function to be called once the web page is loaded.
 **/
    window.onload	= onLoad;

/**
 *  onLoad
 *
 *  Perform initialization functions once the page is loaded.
 *  Each field is enabled for the default keyboard and mouse support.
 **/
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

		    if (element.name == 'selTemplate')
		    {
				selectOptByValue(element, form.template.value);
				element.onchange	= templateChanged;
		    }
		}	// loop through elements in form
    }		// iterate through all forms
}		// onLoad

function templateChanged()
{
    var form        = this.form;
    var	option	    = this.options[this.selectedIndex];

    form.template.value	= option.value;
}		// templateChanged

