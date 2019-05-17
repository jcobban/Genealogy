/************************************************************************
 *  OcfaDoQuery.js										                *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  OcfaDoQuery.html								                    *
 *																		*
 *  History:                    										*
 *		2011/03/20		created	    									*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/06/01      match width of top and bottom page scroll to    *
 *		                width of displayed data                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban.								*
 ************************************************************************/

window.onload	= onLoad

/************************************************************************
 *  function onLoad								                        *
 *																		*
 *  Put the input focus on the next page hyperlink so the user can		*
 *  scroll through multi-page results just by pressing the enter key.	*
 ************************************************************************/
function onLoad()
{
    var nextPage	= document.getElementById('nextPage');
    if (nextPage)
		nextPage.focus();
}		// onLoad

