/**
 *  OcfaDoQuery.js
 *
 *  This file implements the dynamic functionality of the web page
 *  OcfaDoQuery.html
 *
 *  History:
 *	2011/03/20	created
 *	2013/08/01	defer facebook initialization until after load
 *
 *  Copyright &copy; 2013 James A. Cobban.
 **/

window.onload	= onLoad

/**
 *  onLoad
 *
 *  Put the input focus on the next page hyperlink so the user can
 *  scroll through multi-page results just by pressing the enter key.
 **/
function onLoad()
{
    pageInit();

    var nextPage	= document.getElementById('nextPage');
    if (nextPage)
	nextPage.focus();
}		// onLoad

