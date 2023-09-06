/************************************************************************
 *  matchCitations.js													*
 *																		*
 *  This file contains the JavaScript functions that implement the		*
 *  dynamic functionality of the matchCitations.php script used to		*
 *  update a census form to link to individuals in the family tree.		*
 *																		*
 *  History:															*
 *		2012/04/19		created.										*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

// invoke the function onLoad when the page has been completely loaded
window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 ************************************************************************/	
function onLoad()
{
    var	parms	= [];
    var	msg	= "";
    for(var il=0; il < document.links.length; il++)
    {
		var	link	    = document.links[il];
		if (link.id.substring(0,6) == 'ftlink')
		{
		    var	num	    = link.id.substring(6);
		    var href	= link.href;
		    var	ip	    = href.indexOf('=');
		    var	idir	= href.substring(ip + 1);
		    var	lineElt	= document.getElementById('line' + num);
		    var	lineNum	= "";
		    for (var j = 0; j < lineElt.childNodes.length; ++j)
		    {
				var	sub	= lineElt.childNodes[j];
				if ((sub.nodeType == 3) && (sub.nodeValue))
				{		// text node
				    lineNum		= sub.nodeValue.trim();
				    break;
				}		// concatenate all text elements
		    }		// loop through children of source node
		    parms[lineNum]	= idir;
		    msg			+= "parms[" + lineNum + "]=" + idir + ",";
		}
    }
    //alert("matchCitations.js: onLoad: idirFeedback(" + msg + ")");
    if (parms.length > 0)
		window.opener.idirFeedback(parms);
}		// onLoad
