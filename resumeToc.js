/************************************************************************
 *  resumeToc.js							*
 *									*
 *  Implement dynamic functionality specific to the home page.		*
 *									*
 *  History:								*
 *	2015/02/05	created						*
 *									*
 *  Copyright &copy; 2015 James Cobban					*
 ************************************************************************/

window.onload	= resumeLoaded;

/************************************************************************
 *  resumeLoaded							*
 *									*
 *  This method is called when the resume frame has been loaded.	*
 *									*
 *  Input:								*
 *	this	Window object						*
 ************************************************************************/
function resumeLoaded()
{
    var	tabsRow	= document.getElementById("resumeTabsRow");
    if (tabsRow);

    {
	var	cells		= tabsRow.getElementsByTagName("SPAN");
	for (var i = 0; i < cells.length; i++)
	{		// for each data cell in the row
	    var cell	= cells[i];
	    cell.onclick	= resumeSel;	// activate an event method
	}		// for each data cell in the row
    }
}		// indexLoaded

/************************************************************************
 *  resumeSel								*
 *									*
 *  This method is called when the user clicks on a tab.		*
 *									*
 *  Input:								*
 *	this	<span> object						*
 ************************************************************************/
function resumeSel(e)
{
    if (!e)
	e	= window.event;
    var	tabsRow		= this.parentNode;
    var	cells		= tabsRow.getElementsByTagName("SPAN");
    for (var i = 0; i < cells.length; i++)
    {		// for each data cell in the row
	var cell	= cells[i];
	if (cell != this)
	    cell.className	= "tabs";	// set to standard style
    }		// for each data cell in the row
    this.className		= "tabsFront";
    for (i = 0; i < this.childNodes.length; i++)
    {		// loop through span tags
	var child	= this.childNodes[i];
	if (child.nodeName == 'A' && child.href.length > 0)
	{	// hyperlink
	    window.open(child.href, "resume");
	    break;
	}	// hyperlink
    }		// loop through span tags
}		// resumeSel
