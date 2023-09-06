/************************************************************************
 *  Users.js                                                            *
 *                                                                      *
 *  Dynamic functionality of Users.php                                  *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/23      created                                         *
 *      2011/11/28      add support for confirm userid button           *
 *      2012/01/13      change class names                              *
 *      2013/05/29      activate popup help for all fields              *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2014/07/25      add button to reset password                    *
 *      2014/10/25      delete script renamed to deleteUserXml.php      *
 *      2015/07/02      correct error message if deleteUserXml.php      *
 *                      not found on server                             *
 *      2016/01/06      passwords with < or > in them cause XML issues  *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/04/11      use common table pagination                     *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *      2021/05/24      change implementation of confirmUserXml.php     *
 *      2022/06/26      add support for ES2015                          *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {keyDown, debug, popupAlert}
            from "../jscripts6/util.js";
import {change}
            from "../jscripts6/CommonForm.js";

window.addEventListener("load", onLoadUsers);

/************************************************************************
 *  function onLoadUsers                                                *
 *                                                                      *
 *  The onload method of the web page.  This is invoked after the       *
 *  web page has been loaded into the browser.                          *
 ************************************************************************/
function onLoadUsers()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    for (let fi = 0; fi < document.forms.length; fi++)
    {                   // loop through all forms
        let form            = document.forms[fi];

        let formElts        = form.elements;
        for (let i = 0; i < formElts.length; ++i)
        {               // loop through all elements
            let elt         = formElts[i];

            elt.addEventListener('keydown',     keyDown);
            elt.addEventListener('change',      change); 

            if (elt.id.substring(0,'delete'.length) == 'delete')
                elt.addEventListener('click',	deleteUserid);
            else
            if (elt.id.substring(0,'reset'.length) == 'reset')
                elt.addEventListener('click',	resetUserid);
            else
            if (elt.id.substring(0,'confirm'.length) == 'confirm')
                elt.addEventListener('click',	confirmUserid);
        }   // loop through all elements
    }       // loop through all forms

    let dataTable               = document.getElementById('dataTable');
    let dataWidth               = dataTable.offsetWidth;
    let windowWidth             = document.body.clientWidth - 8;
    if (dataWidth > windowWidth)
        dataWidth               = windowWidth;
    let topBrowse               = document.getElementById('topBrowse');
        topBrowse.style.width   = dataWidth + "px";
    let botBrowse               = document.getElementById('botBrowse');
    if (botBrowse)
        botBrowse.style.width   = dataWidth + "px";
}       // function onLoadNames

/************************************************************************
 *  function deleteUserid                                               *
 *                                                                      *
 *  Delete the userid                                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type='button' id='delete...'>               *
 *      ev          instance of click Event                             *
 ************************************************************************/
function deleteUserid(ev)
{
    let iu                  = this.id.substring("delete".length);
    let userid              = document.getElementById('User' + iu).value;
    if (debug.toLowerCase() == 'y')
    {
        alert("Users.js: deleteUserid: {\"user name\"=" + userid + "}");
    }
    let cell                = this.parentNode;
    let row                 = cell.parentNode;
    let inputs              = row.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++)
    {
        let elt             = inputs[i];
        let name            = elt.name;
        let matches         = /^([a-zA-Z_$@#]*)(\d*)$/.exec(name);
        let column          = matches[1].toLowerCase();
        //let id              = matches[2];
        elt.type            = 'hidden';
        if (column == 'auth')
        {
            elt.value       = '';
        }
    }
    this.form.submit();
    ev.stopPropagation();
    return false;
}       // function deleteUserid

/************************************************************************
 *  function resetUserid                                                *
 *                                                                      *
 *  Reset the password of the userid                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type='button' id='reset...'>                *
 *      ev          instance of click Event                             *
 ************************************************************************/
function resetUserid(ev)
{
    let iu              = this.id.substring("reset".length);
    let newPassword     = randomPassword(10);
    let userid          = document.getElementById('User' + iu).value;
    let parms           = { "username" : userid,
                            "password" : newPassword};
    if (debug.toLowerCase() == 'y')
    {
        alert("Users.js: resetUserid: {\"username\" : " + userid +
                            ",\"password\" : " + newPassword + "}");
        parms['debug']  = 'y';
    }

    // update the password for the user
    HTTP.post("updateUserXml.php",
              parms,
              gotReset,
              noReset);
    
    ev.stopPropagation();
    return false;
}       // function resetUserid

/************************************************************************
 *  function randomPassword                                             *
 *                                                                      *
 *  Generate a random password.                                         *
 *  The selection of characters excludes the letters I and O,           *
 *  lower case 'l', and the digits 1 and 0 to avoid misinterpretation.  *
 *                                                                      *
 *  Input:                                                              *
 *      len     number of characters in the resulting password          *
 ************************************************************************/
let passwordAlphabet    =
            "ABCDEFGHJKLMNPQRSTUVWXYZ" +
            "abcdefghjkmnpqrstuvwxyz" +
            "23456789" +
            "!_-+^$@#!~%";

function randomPassword(len)
{
    let newPassword = '';
    for (let i = 0; i < len; i++)
    {
        let index   = Math.floor(Math.random()*passwordAlphabet.length);
        newPassword += passwordAlphabet.charAt(index);
    }
    return newPassword;
}       // function randomPassword

/************************************************************************
 *  function gotReset                                                   *
 *                                                                      *
 *  This method is called when the response to the request to reset     *
 *  the password for a user is received.                                *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc          reply as an XML document                        *
 ************************************************************************/
function gotReset(xmlDoc)
{
    //let evtForm                 = document.evtForm;
    let root                    = xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'update')
    {
        if (debug.toLowerCase() == 'y')
        {
            alert("Users.js:gotReset: xmlDoc=" +
                    new XMLSerializer().serializeToString(root));
        }

        let username                = '';
        let password                = '';
        let id                      = '';
        for (let i = 0; i < root.childNodes.length; i++)
        {                       // loop through all children
            let child               = root.childNodes[i];
            if (child.nodeName == 'parms')
            {
                for (let j = 0; j < child.childNodes.length; j++)
                {               // loop through all children
                    let elt         = child.childNodes[j];
                    if (elt.nodeName == 'username')
                        username    = elt.textContent;
                    else
                    if (elt.nodeName == 'password')
                        password    = elt.textContent;
                }               // loop through all children
            }
            else
            if (child.nodeName == 'id')
            {
                id              = child.textContent;
            }
        }                       // loop through all children
        if (id.length > 0)
            popupAlert("Password for user '" + username +
                    "' reset to '" + password + "'",
                   document.getElementById('reset' + id));
        else
            alert("Password for user '" + username +
                    "' reset to '" + password + "'");
    }
    else
    {       // error
        let msg = "Error: ";
        if (root && root.childNodes)
            msg += new XMLSerializer().serializeToString(root)
        else
            msg += xmlDoc;
        alert ("Users.js: gotReset: "  + msg);
    }       // error
}   // function gotReset

/************************************************************************
 *  function noReset                                                    *
 *                                                                      *
 *  This method is called if there is no response to the AJAX           *
 *  reset password request.                                             *
 ************************************************************************/
function noReset()
{
    alert("Users.js: noReset: " +
          "script resetUserPasswordXml.php not found on server");
}   // function noReset

/************************************************************************
 *  function confirmUserid                                              *
 *                                                                      *
 *  Confirm the userid                                                  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type='button' id='confirm...'>              *
 *      ev          instance of click Event                             *
 ************************************************************************/
function confirmUserid(ev)
{
    let iu  = this.id.substring("confirm".length);
    let userid  = document.getElementById('User' + iu).value;
    let parms       = { "clientid" : userid };
    // update the database
    HTTP.post("confirmUserXml.php",
              parms,
              gotConfirm,
              noConfirm);

    ev.stopPropagation();
    return false;
}       // function confirmUserid

/************************************************************************
 *  function gotConfirm                                                 *
 *                                                                      *
 *  This method is called when the response to the request to confirm   *
 *  a user is received.                                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc          reply as an XML document                        *
 ************************************************************************/
function gotConfirm(xmlDoc)
{
    //let evtForm         = document.evtForm;
    let root            = xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'confirmed')
    {
        let children    = root.childNodes;
        let id          = '';
        for (let i = 0; i < children.length; i++)
        {
            let child   = children[i];
            if (child.nodeName == 'id')
            {
                id = child.textContent;
            }
        }
        let tableRow    = document.getElementById('Row' + id);
        let tableBody   = tableRow.parentNode;
        tableBody.removeChild(tableRow);
    }
    else
    {       // error
        let msg = "Error: ";
        if (root && root.childNodes)
            msg += new XMLSerializer().serializeToString(root)
        else
            msg += xmlDoc;
        alert ("Users.js: gotConfirm: "  + msg);
    }       // error
}   // function gotConfirm

/************************************************************************
 *  function noConfirm                                                  *
 *                                                                      *
 *  This method is called if there is no response to the AJAX           *
 *  confirm event request.                                              *
 ************************************************************************/
function noConfirm()
{
    alert("Users.js: noConfirm: " +
          "script confirmUserXml.php not found on server");
}   // function noConfirm


