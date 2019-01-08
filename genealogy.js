/************************************************************************
 *  genealogy.js														*
 *																		*
 *  Implement the dynamic functionality of the genealogy.html page		*
 *																		*
 *  History:															*
 *		2011/06/24		created											*
 *		2012/01/08		can only change location of page from same host	*
 *		2012/05/26		use location.reload() to refresh page that		*
 *						invoked signon.									*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2015/07/28		close comment blocks							*
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after page is loaded.  This page is			*
 *  frequently invoked by the signon script.  If this is the case then	*
 *  the page that invoked the signon script should be refreshed to		*
 *  reflect the change in user status.									*
 *																		*
 ************************************************************************/
function onLoad()
{
    pageInit();

    var	opener		= window.opener;
    if (opener)
    {			// invoked from another window
		if (opener.location.host == window.location.host)
		{		// refresh
		    if (opener.location.pathname != "/" && 
				opener.location.pathname != "/jamescobban/")
				opener.location.reload();
		}		// refresh
    }			// invoked from another window
}		// onLoad
