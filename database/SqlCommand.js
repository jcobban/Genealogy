/************************************************************************
 *																		*
 *  SqlCommand.js														*
 *																		*
 *  This file contains the JavaScript functions that implement the		*
 *  dynamic functionality of the SqlCommand.php script used to issue		*
 *  a command directly to the SQL database server.						*
 *																		*
 *  History:																*
 *		2011/11/29		created.										*
 *		2014/04/21		set focus in command input field				*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

var helpDiv	= null;

// invoke the function onLoad when the page has been completely loaded
window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after the web page has been loaded.				*
 ************************************************************************/
function onLoad()
{
    // initialize onchange handlers for selected input fields
    // in the form

    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var form		= document.forms[fi];
		var formElts		= form.elements;

		for (var i = 0; i < formElts.length; i++)
		{
		    var element		= formElts[i];
		    var name		= element.name;
		    if (!name || name.length == 0)
				name		= element.id;
    
		    // identify change action for each cell
		    switch(name)
		    {		// switch on column name
				case 'Submit':
				{	// confirm to execute command
				    element.onclick	= issueCommand;
				    break;
				}	// confirm to execute command

				case 'SqlCommand':
				{	// SQL command
				    element.onchange	= changeCommand;
				    element.focus();
				    break;
				}	// SQL command

		    }		// switch on column name
		}		// loop through all form elements
    }			// loop through all forms
}		// onLoad

function changeCommand()
{
    var form			= this.form;
    form.Confirm.value		= '';	// do not execute command
    form.Submit.disabled	= true;
}

function issueCommand()
{
    var form			= this.form;
    form.Confirm.value		= 'Y';	// execute on next invocation
    form.submit();
}
