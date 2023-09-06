

/************************************************************************
 *  function getRangeObject                                             *
 *                                                                      *
 *  Get an object compatible with the W3C Range interface.              *
 *                                                                      *
 *  Input:                                                              *
 *      selectionObject      a Selection or TextRange object            *
 ************************************************************************/
function getRangeObject(selectionObject)
{
    if (selectionObject.getRangeAt)
        return selectionObject.getRangeAt(0);
    else
    {       // Safari 1.3
        let range = document.createRange();
        range.setStart(selectionObject.anchorNode,selectionObject.anchorOffset);
        range.setEnd(selectionObject.focusNode,selectionObject.focusOffset);
        return range;
    }
}

/************************************************************************
 *  function checkRange                                                 *
 *                                                                      *
 *  On a keystroke check the selected range of the document.            *
 *  Under construction.                                                 *
 *                                                                      *
 *  Input:                                                              *
 *      fNode      the node which currently has the focus               *
 ************************************************************************/
function checkRange(fNode)
{
    let userSelection;
    let rangeObject;
    let attrs                   = "";
    if (window.getSelection)
    {                       // W3C compliant
        // this is a Selection object
        userSelection           = window.getSelection();
        for(var attr in userSelection)
            if (userSelection[attr] instanceof HTMLTableCellElement)
                attrs += attr + "=" + new XMLSerializer().serializeToString(userSelection[attr]) + ", ";
            else
            if (typeof userSelection[attr] != "function")
                attrs += attr + "=" + userSelection[attr] + ", ";
            alert("CensusForm.js: checkRange: typeof userSelection:\t" +
                Object.prototype.toString.apply(userSelection) +
          "\n\t" + attrs);
    }                       // W3C compliant
    else
    if (document.selection)
    {                       // IE
        // this is an IE TextRange object
        userSelection  = document.selection.createRange();
        for(var attr in userSelection)
            if (typeof userSelection[attr] != "function")
                attrs += attr + "=" + userSelection[attr] + ", ";
        alert("CensusForm.js: checkRange: typeof userSelection:\t" +
                Object.prototype.toString.apply(userSelection) +
                "\n\t" + attrs);
    }                       // IE

}       // function checkRange
