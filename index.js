/************************************************************************
 *  index.js                                                            *
 *                                                                      *
 *  Implement dynamic functionality specific to the home page.          *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/21      add copyright notice                            *
 *                      add extra cell in tabs table                    *
 *      2011/11/10      click anywhere in a tab displays the            *
 *                      associated page                                 *
 *                      improve separation of HTML and Javascript       *
 *      2013/12/29      use CSS for layout instead of tables            *
 *      2015/02/05      change node id to reflect removal of tables     *
 *      2018/09/15      open new window for genealogy and Blogs         *
 *      2018/10/07      do not open genealogy page twice                *
 *                      do not use window.open                          *
 *      2022/01/28      use addEventListener                            *
 *                                                                      *
 *  Copyright &copy; 2022 James Cobban                                  *
 ************************************************************************/

window.addEventListener("load", indexLoaded, false);

/************************************************************************
 *  function indexLoaded                                                *
 *                                                                      *
 *  This method is called when the document has been loaded.            *
 *                                                                      *
 *  Input:                                                              *
 *      this    Window object                                           *
 ************************************************************************/
function indexLoaded()
{
    let tabsRow = document.getElementById("mainTabsRow");
    if (tabsRow)
    {
        let cells           = tabsRow.getElementsByTagName("SPAN");
        for (let i = 0; i < cells.length; i++)
        {       // for each data cell in the row
            let cell        = cells[i];
            // activate an event handler for mouse clicks
            cell.addEventListener("click",  tabSel);
        }       // for each data cell in the row
    }
}       // function indexLoaded

/************************************************************************
 *  function tabSel                                                     *
 *                                                                      *
 *  This method is called when the user clicks on a tab.                *
 *                                                                      *
 *  Input:                                                              *
 *      this    <span> object                                           *
 *      ev      Mouse Click Event                                       *
 ************************************************************************/
function tabSel(ev)
{
    if (!ev)
        ev                  = window.event;
    ev.stopPropagation();

    let tabsRow             = this.parentNode;
    let cells               = tabsRow.getElementsByTagName("SPAN");
    for (let i = 0; i < cells.length; i++)
    {                   // for each data cell in the row
        let cell            = cells[i];
        if (cell != this)
            cell.className  = "tabs";   // set to standard style
    }                   // for each data cell in the row

    for (let i = 0; i < this.childNodes.length; i++)
    {                   // loop through child tags
        let child           = this.childNodes[i];
        if (child.nodeName == 'A')
        {               // link
            if (child.target == '_blank')
            {           // open new tag
                this.className      = "tabs";
                let mainTab         = document.getElementById('contactsTab');
                mainTab.className   = "tabsFront";
            }
            else
            {
                this.className      = "tabsFront";
            }
            break;
        }               // link
    }                   // loop through child tags
}       // function tabSel
