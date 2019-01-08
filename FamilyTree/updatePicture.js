/************************************************************************
 *  updatePicture.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page updatePicture.php.						*
 *									*
 *  History:								*
 *	2011/05/28	created						*
 *	2012/01/08	can only change location of page from same host	*
 *	2012/01/13	change class names				*
 *	2015/02/10	support being opened in <iframe>		*
 *	2016/02/06	call pageInit on load				*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/

    window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize elements.						*
 *									*
 *  Input:								*
 *	this	Window							*
 ************************************************************************/
function onLoad()
{
    pageInit();

    var	form				= document.updForm;

    if (form.action == '')
    {
	var	opener	= null;
	if (window.frameElement && window.frameElement.opener)
	    opener	= window.frameElement.opener;
	else
	    opener	= window.opener;
	if (opener)
	{		// invoked from another page
	    if (opener.location.host == window.location.host)
		opener.location.reload();
	}		// invoked from another page
	window.close();
    }

    // set action methods for elements
    form.Close.onclick		 	= closeWindow();

}		// onLoad

/************************************************************************
 *  closeWindow								*
 *									*
 *  Close the window in response to user action.			*
 *									*
 *  Input:								*
 *	this	<button id='Close'>					*
 ************************************************************************/
function closeWindow()
{
    window.close();
}		// closeWindow
