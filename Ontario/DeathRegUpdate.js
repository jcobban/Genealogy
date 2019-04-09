/************************************************************************
 *  DeathRegUpdate.js						                            *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the			*
 *  page DeathRegUpdate.php.						                    *
 *                                                                      *
 *  History:						                                    *
 *	    2011/04/22	    create                  						*
 *	    2013/08/01	    defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban						        *
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad				                                        *
 *                                                                      *
 *  This function is called when the web page is loaded to perform		*
 *  dynamic initialization.						                        *
 *  The input focus is set to the hyperlink to update the next death	*
 *  registration so the user can just press enter to proceed.			*
 ************************************************************************/
function onLoad()
{
    var updNext	= document.getElementById('updNext');

    // if the user is not authorized to edit the database there is no
    // element with id 'updNext'
    if (updNext)
	updNext.focus();
}		// onLoad
