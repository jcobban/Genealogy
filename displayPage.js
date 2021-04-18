/************************************************************************
 *  DisplayPage.js                                                      *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  pages displayed by displayPage.php.                                 *
 *                                                                      *
 *  History:                                                            *
 *      2018/02/03      created                                         *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2021/04/02      use ES2015 import                               *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.    *
 *                                                                      *
 *  Define the function to be called once the web page is loaded.       *
 ************************************************************************/
import {simpleMouseOver, eltMouseOut, getOffsetLeft, getOffsetTop,
        showHelp}
            from "../jscripts6/util.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  onLoad                                                              *
 *                                                                      *
 *  Perform initialization functions once the page is loaded.           *
 ************************************************************************/
function onLoad()
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
        let form    = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {
            let element = form.elements[j];
    
            let name    = element.name;
            if (name.length == 0)
            {       // button elements usually have id not name
                name    = element.id;
            }       // button elements usually have id not name
    
            let parts   = /^([a-zA-Z_]+)(\d*)$/.exec(name);
            if (parts)
            {
                name    = parts[1];
            }
    
        }   // loop through elements in form
    }       // iterate through all forms

    // activate support for a popup on each hyperlink to an individual
    let allAnc  = document.getElementsByTagName("a");
    for (var ianc = 0, maxAnc = allAnc.length; ianc < maxAnc; ianc++)
    {       // loop through all anchors
        let anc                 = allAnc[ianc];
        let li                  = anc.href.lastIndexOf('/');
        let name                = anc.href.substring(li + 1);
        let hi                  = name.indexOf('#');
        if (hi == -1 && name.substring(0, 13) == "Person.php?id")
        {   // link to another individual
            anc.onmouseover     = indMouseOver;
            anc.onmouseout      = eltMouseOut;
        }   // link to another individual
    }       // loop through all anchors

}       // function onLoad

/************************************************************************
 *  indMouseOver                                                        *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  containing a hyperlink to an individual on the invoking page.       *
 *  Delay popping up the information balloon for two seconds.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <a> tag                                             *
 *      ev          mouse over Event                                    *
 ************************************************************************/
function indMouseOver(ev)
{
    simpleMouseOver.call(this, ev, popupIndiv);
}       // function indMouseOver

/************************************************************************
 *  function popupIndiv                                                 *
 *                                                                      *
 *  This function is called if the mouse is held over a link to an      *
 *  individual on the invoking page for more than 2 seconds.  It shows  *
 *  the information from the associated instance of Person              *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML Element                                        *
 ************************************************************************/
function popupIndiv()
{
    let regex               = /idir=(\d+)/;
    let matches             = this.href.match(regex);
    if (matches)
    {
        let idir            = matches[1];
        let popupDiv         = document.getElementById("Individ" + idir);

        if (popupDiv)
        {       // have the division

            // position and display division
            let leftOffset      = getOffsetLeft(this);
            if (leftOffset > (window.innerWidth / 2))
                leftOffset      = window.innerWidth / 2;
            popupDiv.style.left  = leftOffset + "px";
            popupDiv.style.top   = (getOffsetTop(this) + 30) + 'px';
            showHelp.call(this, popupDiv)
        }       // have the division to display
        else
            alert("util.js: popupIndiv: Cannot find <div id='Individ" +
                  idir + "'>");
    }
}       // popupIndiv
