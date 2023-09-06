/************************************************************************
 *  ancestorReport.js                                                   *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page ancestorReport.php.                                            *
 *                                                                      *
 *  History:                                                            *
 *      2010/12/29      created                                         *
 *      2011/04/06      add option to display locations                 *
 *      2012/01/13      change class names                              *
 *      2013/07/30      defer facebook initialization until after load  *
 *                      standardize initialization and activate help    *
 *      2015/01/23      add close button                                *
 *      2015/02/10      use closeFrame                                  *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2023/07/29      migrate to Es2015                               *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {closeFrame}
            from "../jscripts6/util.js";

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize dynamic functionality of elements.                       *
 ************************************************************************/
function onLoad()
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {                   // iterate through all forms
        var form            = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {               // loop through elements in form
            var element     = form.elements[j];

            var name        = element.name;
            if (name === undefined || name.length == 0)
                name        = element.id;

            // take action specific to the element based on its name
            switch(name)
            {           // switch on name
                case "ancDepth":
                {
                    element.addEventListener('change', recalculate);
                    break;
                }       // ancDepth

                case "incLocsSet":
                {
                    element.addEventListener('change', changeLocs);
                    break;
                }       // incLocsSet

                case "Close":
                {
                    element.addEventListener('click', close);
                    break;
                }       // close

            }           // switch on name
        }               // loop through elements in form
    }                   // iterate through all forms
}       // function onLoad

/************************************************************************
 *  function changeLocs                                                 *
 *                                                                      *
 *  This method is called when the user modifies the value of           *
 *  the include locations checkbox.                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <input name='incLocsSet'>                           *
 ************************************************************************/
function changeLocs()
{
    var form                = this.form;
    if (this.checked)
        form.incLocs.value  = 1;
    else
        form.incLocs.value  = 0;
    form.submit();
}       // function changeLocs

/************************************************************************
 *  function recalculate                                                *
 *                                                                      *
 *  This method is called when the user modifies the value of           *
 *  the tree depth.                                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    <input name='ancDepth'>                                 *
 ************************************************************************/
function recalculate()
{
    var form        = this.form;
    form.submit();
}       // function recalculate

/************************************************************************
 *  function close                                                      *
 *                                                                      *
 *  This method is called when the user clicks on the button to close   *
 *  the dialog.                                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <button id='Close'>                                 *
 ************************************************************************/
function close()
{
    closeFrame();
}       // function close
