/************************************************************************
 *  OcfaStats.js										                *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  OcfaStats.php										                *
 *																		*
 *  History:										                    *
 *		2012/05/07		created										    *
 *		2013/08/01		defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad								                        *
 *																		*
 *  Perform dynamic initialization for the page							*
 ************************************************************************/
function onLoad()
{
    // define dynamic actions and
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    // set up dynamic functionality based on the name of the element
		    if (name.substring(0, 15) == "ShowCountyStats")
		    {
				element.onclick	= showStatus;
				element.helpDiv	= "ShowCountyStats";
		    }
		}	// loop through all elements in the form
    }		// loop through forms in the page
}		// onLoad

/************************************************************************
 *  function showStatus								                    *
 *																		*
 *  onclick method for a county status button.							*
 *  Switch to the transcription status page for the specific county.	*
 ************************************************************************/
function showStatus()
{
    location	= "OcfaCountyStats.php?county=" + this.id.substring(15);
    return false;	// suppress default action
}		// showStatus

