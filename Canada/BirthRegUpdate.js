/**
 *  BirthRegUpdate.js
 *
 *  Javascript code to implement dynamic functionality of the
 *  page BirthRegUpdate.html.
 *
 *  History:
 *	2011/02/27	create
 *	2013/08/01	defer facebook initialization until after load
 *
 *  Copyright &copy; 2013 James A. Cobban
 **/

window.onload	= loadScript;

/**
 *  loadScript
 *
 *  This function is called when the web page is loaded to perform
 *  dynamic initialization.
 *  The input focus is set to the hyperlink to update the next birth
 *  registration so the user can just press enter to proceed.
 **/
function loadScript()
{
    pageInit();

    var updNext	= document.getElementById('updNext');
    updNext.focus();
}		// loadScript
