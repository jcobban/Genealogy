/************************************************************************
 *  Advertiser.js                                                       *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  Advertiser.php                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2020/01/17      created                                         *
 *      2021/04/01      use ES2015 import                               *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
import {eltMouseOver, eltMouseOut, keyDown} from "../jscripts6/util.js";
import {changeElt} from "../jscripts6/CommonForm.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize the dynamic functionality once the page is loaded        *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var element;
    var trace           = '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {       // loop through all forms
        var form        = document.forms[fi];
        trace           += "<form";
        if (form.name.length > 0)
            trace       += " name='" + form.name + "'";
        if (form.id.length > 0)
            trace       += " id='" + form.id + "'";
        trace           += ">";

        for (var i = 0; i < form.elements.length; ++i)
        {   // loop through all elements of form
            element                     = form.elements[i];
            trace                       += "<" + element.nodeName;
            if (element.name.length > 0)
                trace                   += " name='" + element.name + "'";
            if (element.id.length > 0)
                trace                   += " id='" + element.id + "' ";
            trace                       += ">";
            element.onkeydown           = keyDown;

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            var mouseOn                 = element;
            if (element.parentNode.nodeName == 'TD')
            {   // set mouseover on containing cell
                mouseOn                 = element.parentNode;
            }   // set mouseover on containing cell
            mouseOn.onmouseover         = eltMouseOver;
            mouseOn.onmouseout          = eltMouseOut;

            var name                    = element.name;
            if (name.length == 0)
                name                    = element.id;
            name                        = name.toLowerCase();

            switch (name)
            {       // act on a field from a table row
                case 'ademail':
                {       // advertiser email
                    element.onchange    = changeEmail;
                    break;
                }       // advertiser email

                case 'upload':
                {       // upload new advertisement
                    element.onclick     = uploadAdvertisement;
                    break;
                }       // delete this advertiser

            }           // act on a field
        }               // loop through all elements in the form
    }                   // loop through all forms
    console.log("Advertiser.js: onLoad: " + trace);

}       // function onLoad

/************************************************************************
 *  function changeEmail                                                *
 *                                                                      *
 *  Take action when the user changes the advertiser email.             *
 *                                                                      *
 *  Input:                                                              *
 *      $this           instance of <input name='AdEmail...'>           *
 ************************************************************************/
function changeEmail()
{
    changeElt(this);
}       // function changeEmail

/************************************************************************
 *  function uploadAdvertisement                                        *
 *                                                                      *
 *  When the Upload button is clicked the browser uploads the updated   *
 *  advertisement to the server.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button type=button id='upload'>                *
 *      ev              Click Event                                     *
 ************************************************************************/
function uploadAdvertisement(ev)
{
    ev.stopPropagation();

    var fileelt             = document.getElementById('file');
    var count               = fileelt.files.length;
    if (count == 0)
    {
        fileelt.addEventListener('change',fileSelected);
        fileelt.click();
    }
    else
    {
        let file            = fileelt.files[0];
        alert("file=" + file.name);
    }

    return false;
}       // function uploadAdvertisement

/************************************************************************
 *  function fileSelected                                               *
 *                                                                      *
 *  When the user selects a file to upload this is called.              *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button type=file id='file'>                    *
 *      ev              Change Event                                    *
 ************************************************************************/
function fileSelected(ev)
{
    ev.stopPropagation();

    let file            = this.files[0];
    console.log("Advertiser.js: fileSelected: file=" + file.name);
    this.form.submit();
}       // function fileSelected
