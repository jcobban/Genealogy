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
 *		2019/02/11      scroll just the main section, leaving header    *
 *		                and footer visible always                       *
 *		                calling pageInit no longer required             *
 *		2019/12/30      scrolling main moved to util.js                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

if (window.addEventListener) 
{                    // For all major browsers, except IE 8 and earlier
    window.addEventListener("load", onLoad, false);
} 
else 
if (window.attachEvent) 
{                  // For IE 8 and earlier versions
    window.attachEvent("onload", onLoad)
}

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after page is loaded.  This page is			*
 *  frequently invoked by the signon script.  If this is the case then	*
 *  the page that invoked the signon script should be refreshed to		*
 *  reflect the change in user status.									*
 *																		*
 *	Input:																*
 *	    event       instance of Event containing load event             *
 *	    this        instance of Window                                  *
 ************************************************************************/
function onLoad(event)
{
    var	opener		= window.opener;
    try {
    if (opener)
    {			// invoked from another window
		if (opener.location.host == window.location.host)
		{		// refresh
		    if (opener.location.pathname != "/" && 
				opener.location.pathname != "/jamescobban/")
				opener.location.reload();
		}		// refresh
    }			// invoked from another window
    }
    catch(error) {}

}		// onLoad
