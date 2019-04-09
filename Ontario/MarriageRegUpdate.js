/************************************************************************
 *  MarriageRegUpdate.js					                            *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  MarriageRegUpdate.php.					                            *
 *																		*
 *  History:					                                        *
 *	    2011/03/14	    created					                        *
 *	    2013/08/01	    defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban					            *
 ************************************************************************/
 
window.onload	= onLoad;

/************************************************************************
 *  function onLoad			                                            *
 *																		*
 *  This function is called after the associated web page,				*
 *  MarriageRegUpdate.php, is completely loaded into the DOM.			*
 ************************************************************************/
function onLoad()
{
    var updNext	= document.getElementById('updNext');
    if (updNext)
    {		// is null if user not authorized to update
	    updNext.focus();
    }		// is null if user not authorized to update
}		// onLoad
