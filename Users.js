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
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

window.onload   = onLoadUsers;

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
    for (var fi = 0; fi < document.forms.length; fi++)
    {       // loop through all forms
        var form    = document.forms[fi];

        var formElts    = form.elements;
        for (var i = 0; i < formElts.length; ++i)
        {   // loop through all elements
            var elt = formElts[i];
            var name;
            if (elt.name && elt.name.length > 0)
            name    = elt.name;
            else
            name    = elt.id;

            elt.onkeydown   = keyDown;
            elt.onchange    = change;   // default handler

            if (elt.id.substring(0,'delete'.length) == 'delete')
                elt.onclick = deleteUserid;
            else
            if (elt.id.substring(0,'reset'.length) == 'reset')
                elt.onclick = resetUserid;
            else
            if (elt.id.substring(0,'confirm'.length) == 'confirm')
                elt.onclick = confirmUserid;
        }   // loop through all elements
    }       // loop through all forms

    var dataTable           = document.getElementById('dataTable');
    var dataWidth           = dataTable.offsetWidth;
    var windowWidth         = document.body.clientWidth - 8;
    if (dataWidth > windowWidth)
        dataWidth           = windowWidth;
    var topBrowse           = document.getElementById('topBrowse');
        topBrowse.style.width   = dataWidth + "px";
    var botBrowse           = document.getElementById('botBrowse');
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
    var iu                  = this.id.substring("delete".length);
    var userid              = document.getElementById('User' + iu).value;
    if (debug.toLowerCase() == 'y')
    {
        alert("Users.js: deleteUserid: {\"user name\"=" + userid + "}");
    }
    var cell                = this.parentNode;
    var row                 = cell.parentNode;
    var inputs              = row.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++)
    {
        var elt             = inputs[i];
        var name            = elt.name;
        var matches         = /^([a-zA-Z_$@#]*)(\d*)$/.exec(name);
        var column          = matches[1].toLowerCase();
        var id              = matches[2];
        elt.type            = 'hidden';
        if (column == 'auth')
        {
            elt.value       = '';
        }
    }
    this.form.submit();
}       // function deleteUserid

/************************************************************************
 *  function resetUserid                                                *
 *                                                                      *
 *  Reset the password of the userid                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type='button' id='reset...'>                *
 ************************************************************************/
function resetUserid()
{
    var iu              = this.id.substring("reset".length);
    var newPassword     = randomPassword(10);
    var userid          = document.getElementById('User' + iu).value;
    var parms           = { "username" : userid,
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
var passwordAlphabet    =
            "ABCDEFGHJKLMNPQRSTUVWXYZ" +
            "abcdefghjkmnpqrstuvwxyz" +
            "23456789" +
            "!_-+^$@#!~%";
function randomPassword(len)
{
    var newPassword = '';
    for (var i = 0; i < len; i++)
    {
        var index   = Math.floor(Math.random()*passwordAlphabet.length);
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
    var evtForm = document.evtForm;
    var root    = xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'update')
    {
        if (debug.toLowerCase() == 'y')
        {
            alert("Users.js:gotReset: xmlDoc=" + new XMLSerializer().serializeToString(root));
        }

        var username            = '';
        var password            = '';
        var id                  = '';
        for (var i = 0; i < root.childNodes.length; i++)
        {               // loop through all children
            var child           = root.childNodes[i];
            if (child.nodeName == 'parms')
            {
                for (var j = 0; j < child.childNodes.length; j++)
                {       // loop through all children
                    var elt = child.childNodes[j];
                    if (elt.nodeName == 'username')
                        username    = elt.textContent;
                    else
                    if (elt.nodeName == 'password')
                        password    = elt.textContent;
                }       // loop through all children
            }
            else
            if (child.nodeName == 'id')
            {
                id              = child.textContent;
            }
        }               // loop through all children
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
        var msg = "Error: ";
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
 ************************************************************************/
function confirmUserid()
{
    var iu  = this.id.substring("confirm".length);
    var userid  = document.getElementById('User' + iu).value;
    var parms       = { "clientid" : userid };
    // update the database
    HTTP.post("confirmUserXml.php",
              parms,
              gotConfirm,
              noConfirm);
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
    var evtForm         = document.evtForm;
    var root            = xmlDoc.documentElement;
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
        var msg = "Error: ";
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


