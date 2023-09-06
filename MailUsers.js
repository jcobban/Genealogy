/************************************************************************
 *  MailUsers.js                                                        *
 *                                                                      *
 *  Implement the dynamic functionality of the send bulk mail           *
 *  script.                                                             *
 *                                                                      *
 *  History:                                                            *
 *      2022/06/01      created                                         *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
import {eltMouseOver, eltMouseOut, keyDown, args, popupAlert}
            from "../jscripts6/util.js";

window.addEventListener("load", onLoad);

let timeId                  = null;
let formGbl                 = null;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization of dynamic functionality after page is       *
 *  loaded.                                                             *
 *                                                                      *
 ************************************************************************/
function onLoad()
{
    let bodytext            = '';
    let offset              = 0;
    let count               = Number.MAX_SAFE_INTEGER;

    for(let i = 0; i < document.forms.length; i++)
    {                   // loop through forms
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {               // loop through elements
            let element     = form.elements[j];
            let name        = element.name;
            if(!name || name.length == 0)
                name        = element.id;

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            element.onmouseover     = eltMouseOver;
            element.onmouseout      = eltMouseOut;
            element.onkeydown       = keyDown;

            switch(name)
            {           // act on specific element
                case 'body':
                    formGbl         = form
                    let editor      = tinyMCE.get(name);
                    if (editor)
                    {
                        editor.focus();     // put focus in editor
                        bodytext    = editor.getContent();
                    }
                    else
                    {
                        element.focus();    // put focus in body text field
                        bodytext    = element.value;
                    }
                    break;              // body

                case 'offset':
                    offset          = element.value;
                    break;              // offset

                case 'count':
                    count           = element.value;
                    break;
            }           // act on specific element
        }               // loop through elements in form
    }                   // loop through all form elements

    let sendButton          = document.getElementById('Send');
    if (sendButton)
        sendButton.addEventListener("click", firstMail);

    if (bodytext.length > 0 && offset < count)
    {
        timeId              = setTimeout(sendNextTranche, 1000);
        console.log("MailUsers.js: onLoad: offset=" + offset + ", count=" + count + ", start 1 minute time delay");
    }

}       // function onLoad

/************************************************************************
 *  function firstMail                                                  *
 *                                                                      *
 *  Reinvoke the PHP script to send the first bulk mail message.        *
 *                                                                      *
 ************************************************************************/
function firstMail(ev)
{
    let form                = document.getElementById('locForm');
    let bodyEditor          = tinyMCE.get('body');
    let body                = '';
    if (bodyEditor)
        body                = bodyEditor.getContent();
    else
        body                = document.getElementById('body').value;
    if (body.length > 0)
    {
        form.submit();
        let offset          = document.getElementById('offset');
        let count           = document.getElementById('count');
        if (offset.value < count.value)
        {
            timeId          = setTimeout(sendNextTranche, 1000);
            console.log("MailUser.js: firstMail: offset=" + offset + 
                        ", count=" + count + ", start 1 minute time delay");
        }
    }
    else
        popupAlert('Message body not supplied', this);
    ev.stopPropagation();
}       // function firstMail

/************************************************************************
 *  function sendNextTranche                                            *
 *                                                                      *
 *  Send the next e-mail after a one minute delay.                      *
 *                                                                      *
 ************************************************************************/
function sendNextTranche()
{
    console.log("sendNextTranche: offset=" + formGbl.offset.value);
    formGbl.submit();
}       // function sendNextTranche
