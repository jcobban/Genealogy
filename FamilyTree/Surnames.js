/************************************************************************
 *  Surnames.js                                                         *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Surnames.php.                                                  *
 *                                                                      *
 *  History:                                                            *
 *      2011/10/31      created                                         *
 *      2012/01/13      change class names                              *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2022/06/04      use ES2015                                      *
 *                      activate click on surname buttons               *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/

window.addEventListener("load", onloadSurnames);

/************************************************************************
 *  function onloadSurnames                                             *
 *                                                                      *
 *  Initialize elements.                                                *
 ************************************************************************/
function onloadSurnames()
{
    let table           = document.getElementById('namesTable');
    let children        = table.children;
    for (let i = 0; i < children.length; i++) 
    {
        children[i].addEventListener("click", followLink);
    }

}       // function onloadSurnames

/************************************************************************
 *  function followLink                                                 *
 *                                                                      *
 *  This is the onclick method for a table cell that contains an <a>    *
 *  element.  When this cell is clicked on, it acts as if the mouse     *
 *  was clicking on the contained <a> tag.                              *
 *                                                                      *
 *  Input:                                                              *
 *      ev          event                                               *
 *      this        table cell node                                     *
 ************************************************************************/
function followLink(ev)
{
    if (ev)
        ev.stopPropagation();
    for(let ie = 0; ie < this.childNodes.length; ie++)
    {               // loop through all children
        let node        = this.childNodes[ie];
        if (node.nodeName == 'A')
        {           // anchor node
            location    = node.href;
            return false;
        }           // anchor node
    }               // loop through all children
    return false;
}       // function followLink
