/************************************************************************
 *  WmbDistrictStats.js					                                *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  WmbDistrictStats.php					                            *
 *																		*
 *  History:					                                        *
 *	    2019/07/10	    created					                        *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.					            *
 ************************************************************************/

window.onload	= loadStats;

/************************************************************************
 *  function loadStats			                                        *
 *																		*
 *  Perform dynamic initialization for the page					        *
 ************************************************************************/
function loadStats()
{
    if (!('columns' in args))
    {                       // invoker did not explicitly specify width
        var dataTable       = document.getElementById('dataTable');
        if (dataTable)
        {                   // found table
            var tableWidth  = dataTable.offsetWidth;
            var optColumns  = Math.floor(window.innerWidth / tableWidth);
            location.href   = location.href + "&columns=" + optColumns;
            return;
        }                   // found table
    }                       // invoker did not explicitly specify width

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
		    if (name.substring(0, 17) == "ShowDistrictStats")
		    {
				element.onclick	= showStatus;
				element.helpDiv	= "ShowDistrictStats";
		    }
		}	// loop through all elements in the form
    }		// loop through forms in the page
}		// loadStats

/************************************************************************
 *  function showStatus		                                        	*
 *																		*
 *  onclick method for a district status button.					    *
 *  Switch to the transcription status page for the specific district.	*
 ************************************************************************/
function showStatus()
{
    location	= "WmbDistrictStats.php?district=" + encodeURIComponent(this.value);
    return false;	// suppress default action
}		// showStatus

