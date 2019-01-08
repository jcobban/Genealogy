/**
 *  MarriageRegUpdate.js
 *
 *  This file implements the dynamic functionality of the web page
 *  MarriageRegUpdate.php.
 *
 *  History:
 *	2011/03/14	created
 *	2013/08/01	defer facebook initialization until after load
 *
 *  Copyright &copy; 2013 James A. Cobban
 **/
 
window.onload	= onLoad;

/**
 *  onLoad
 *
 *  This function is called after the associated web page,
 *  MarriageRegUpdate.php, is completely loaded into the DOM.
 **/
function onLoad()
{
    pageInit();

    var updNext	= document.getElementById('updNext');
    if (updNext)
    {		// is null if user not authorized to update
	updNext.focus();
    }		// is null if user not authorized to update
}		// onLoad
