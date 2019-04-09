/************************************************************************
 *  DisplayPage.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  pages displayed by displayPage.php.									*
 *																		*
 *  History:															*
 *		2018/02/03		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization functions once the page is loaded.				*
 ************************************************************************/
function onLoad()
{

    var names	= "";
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
	var form	= document.forms[i];
	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    var	name	= element.name;
	    if (name.length == 0)
	    {		// button elements usually have id not name
		name	= element.id;
	    }		// button elements usually have id not name

	    var parts	= /^([a-zA-Z_]+)(\d*)$/.exec(name);
	    if (parts)
	    {
		name	= parts[1];
	    }

	}	// loop through elements in form
    }		// iterate through all forms

    // activate support for a popup on each hyperlink to an individual
    var	allAnc	= document.getElementsByTagName("a");
    for (var ianc = 0, maxAnc = allAnc.length; ianc < maxAnc; ianc++)
    {		// loop through all anchors
	var	anc	= allAnc[ianc];
	var	li	= anc.href.lastIndexOf('/');
	var	name	= anc.href.substring(li + 1);
	var	hi	= name.indexOf('#');
	if (hi == -1 && name.substring(0, 13) == "Person.php?id")
	{	// link to another individual
	    anc.onmouseover		= indMouseOver;
	    anc.onmouseout		= indMouseOut;
	}	// link to another individual
    }		// loop through all anchors

}		// onLoad

/************************************************************************
 *  indMouseOver														*
 *																		*
 *  This function is called if the mouse moves over an element				*
 *  containing a hyperlink to an individual on the invoking page. 		*
 *  Delay popping up the information balloon for two seconds.				*
 *																		*
 *  Input:																*
 *		this		<a> tag														*
 ************************************************************************/
function indMouseOver()
{
    // this method reuses the display management fields from popup help
    helpElt		= this;
    helpDelayTimer	= setTimeout(popupIndiv, 2000);
}		// indMouseOver

/************************************************************************
 *  popupIndiv																*
 *																		*
 *  This function is called if the mouse is held over a link to an		*
 *  individual on the invoking page for more than 2 seconds.  It shows		*
 *  the information from the associated instance of Person				*
 ************************************************************************/
function popupIndiv()
{
    var	indIndex	= helpElt.href.indexOf('=');
    if (indIndex >= 0)
    {
	var idir	= helpElt.href.substring(indIndex + 1);
	var ampPos	= idir.indexOf('&');
	if (ampPos > 0)
	    idir	= idir.substring(0,ampPos);

	// if a previous help balloon is still being displayed, hide it
	if (helpDiv)
	{		// a help division is currently displayed
	    helpDiv.style.display	= 'none';
	    helpDiv			= null;
	}		// a help division is currently displayed

	helpDiv	= document.getElementById("Individ" + idir);

	if (helpDiv)
	{		// have the division

	    // position and display division
	    var leftOffset	= getOffsetLeft(helpElt);
	    if (leftOffset > (window.innerWidth / 2))
		leftOffset	= window.innerWidth / 2;
	    helpDiv.style.left	= leftOffset + "px";
	    helpDiv.style.top	= (getOffsetTop(helpElt) + 30) + 'px';
	    show(helpDiv)
//alert("util.js: popupSource: helpDiv.style.left=" + helpDiv.style.left +
//			", helpDiv.style.top=" + helpDiv.style.top);
	}		// have the division to display
	else
	    alert("util.js: popupIndiv: Cannot find <div id='Individ" +
		  idir + "'>");
    }
}		// popupIndiv

/************************************************************************
 *  indMouseOut																*
 *																		*
 *  This function is called if the mouse moves off an element				*
 *  containing a indiv name on the invoking page. 						*
 *  The help balloon, if any, remains up for								*
 *  a further 2 seconds to permit access to links within the help text.		*
 *																		*
 *  Input:																*
 *		this	<a> tag													*
 ************************************************************************/
function indMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer	= setTimeout(hideHelp, 2000);
}		// indMouseOut
