/************************************************************************
 *  resumeToc.js                                                        *
 *                                                                      *
 *  Implement dynamic functionality specific to the home page.          *
 *                                                                      *
 *  History:                                                            *
 *      2015/02/05      created                                         *
 *      2022/06/24      support ES2015                                  *
 *                                                                      *
 *  Copyright &copy; 2022 James Cobban                                  *
 ************************************************************************/

window.addEventListener("load", resumeLoaded);

/************************************************************************
 *  resumeLoaded                                                        *
 *                                                                      *
 *  This method is called when the resume frame has been loaded.        *
 *                                                                      *
 *  Input:                                                              *
 *      this    Window object                                           *
 ************************************************************************/
function resumeLoaded()
{
    var tabsRow = document.getElementById("resumeTabsRow");
    if (tabsRow)
    {
        var cells           = tabsRow.getElementsByTagName("SPAN");
        for (var i = 0; i < cells.length; i++)
        {               // for each data cell in the row
            var cell        = cells[i];
            cell.addEventListener("click",      resumeSel)
        }               // for each data cell in the row
    }
}       // function resumeLoaded

/************************************************************************
 *  resumeSel                                                           *
 *                                                                      *
 *  This method is called when the user clicks on a tab.                *
 *                                                                      *
 *  Input:                                                              *
 *      this    <span> Element                                          *
 *      e       Event                                                   *
 ************************************************************************/
function resumeSel(e)
{
    if (!e)
        e               = window.event;
    var tabsRow         = this.parentNode;
    var cells           = tabsRow.getElementsByTagName("SPAN");
    for (var i = 0; i < cells.length; i++)
    {               // for each data cell in the row
        var cell        = cells[i];
        if (cell != this)
            cell.className  = "tabs";   // set to standard style
    }               // for each data cell in the row
    this.className      = "tabsFront";
    for (i = 0; i < this.childNodes.length; i++)
    {               // loop through span tags
        var child       = this.childNodes[i];
        if (child.nodeName == 'A' && child.href.length > 0)
        {           // hyperlink
            window.open(child.href, "resume");
            break;
        }           // hyperlink
    }               // loop through span tags
}       // function resumeSel
