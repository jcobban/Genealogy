/************************************************************************
 *  PageUpdate.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page PageUpdate.php													*
 *																		*
 *  History:															*
 *		2010/10/19		add header and copyright notice					*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= loadPages;

/************************************************************************
 *  function loadPages													*
 *																		*
 *  Perform initialization of dynamic functionality on page load.		*
 *  Set focus on the button that user's are most likely to want to		*
 *  click on so that the Enter key will request that function.			*
 ************************************************************************/
function loadPages()
{
    if (document.actForm.nextDiv)
		document.actForm.nextDiv.focus();
    else
		document.actForm.newReq.focus();
}		// loadPages

