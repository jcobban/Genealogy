/************************************************************************
 *  Messages.js                                                         *
 *                                                                      *
 *  Implement the dynamic functionality of the account management       *
 *  script.                                                             *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/30      created                                         *
 *      2011/03/10      on sign off clear userid out of rightTop button *
 *                      of opener                                       *
 *      2011/04/22      IE does not implement form.elements correctly   *
 *      2012/01/05      use id rather than name for buttons to avoid    *
 *                      passing them to the action script in IE         *
 *                      change signoff to alt-O to match Signon script  *
 *      2012/05/28      add mouse-over help balloons                    *
 *      2014/03/27      add on the fly validation of input fields       *
 *      2014/08/29      add blog messages support                       *
 *      2018/02/05      changed to support template                     *
 *      2018/02/28      add random password generator                   *
 *                      add score for supplied password                 *
 *      2018/10/18      pass language to scripts initiated by buttons   *
 *      2018/12/21      increase probability of digits and letters      *
 *                      in generated password, and trim password        *
 *      2019/02/06      session status moved to link in menu            *
 *      2019/02/08      use addEventListener                            *
 *      2019/12/03      change random password generator to exclude "   *
 *      2020/04/23      increase generated password length to 32        *
 *      2021/04/04      support ES2015                                  *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {eltMouseOver, eltMouseOut, keyDown}
            from "../jscripts6/util.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization of dynamic functionality after page is       *
 *  loaded.                                                             *
 *                                                                      *
 ************************************************************************/
function onLoad()
{
    for(var i = 0; i < document.forms.length; i++)
    {                   // loop through forms
        let form    = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {               // loop through elements
            let element     = form.elements[j];
            let name        = element.name;
            if(!name || name.length == 0)
                name        = element.id;
            name            = name.toLowerCase();

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            element.onmouseover     = eltMouseOver;
            element.onmouseout      = eltMouseOut;
            element.onkeydown       = keyDown;

            switch(name)
            {           // act on specific element
                case 'close':
                {
                    element.onclick = finish;
                    break;
                }

                default:
                {       // others
                    if (name.substring(0, 6) == 'delete')
                        element.onclick = deleteBlog;
                    else
                    if (name.substring(0, 5) == 'reply')
                        element.onclick = replyBlog;
                    break;
                }       // others

            }           // act on specific element
        }               // loop through elements in form
    }                   // loop through all form elements
}       // function onLoad

/************************************************************************
 *  function finish                                                     *
 *                                                                      *
 *  Close the current window                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Close'>                             *
 ************************************************************************/
function finish()
{
    window.close();
}       // finish

/************************************************************************
 *  deleteBlog                                                          *
 *                                                                      *
 *  This method is called when the user requests to delete a specific   *
 *  message. This is the onclick method of <button id='Delete...'>      *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Delete...'>                             *
 ************************************************************************/
function deleteBlog()
{
    let blid        = this.id.substring(6);
    // get the subdistrict information file
    let parms       = {'id'     : blid};

    HTTP.post("deleteBlogXml.php",
              parms,
              gotDelete,
              noDelete);
    return true;
}   // deleteDelete

/************************************************************************
 *  gotDelete                                                           *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the deletion of the blog is received.                               *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML response file describing the deletion           *
 *                  of the message                                      *
 ************************************************************************/
function gotDelete(xmlDoc)
{
    let root        = xmlDoc.documentElement;
    let text        = '';
    let msgs        = root.getElementsByTagName('msg');
    for (let item of msgs)
    {
        text        += item.textContent;
    }
    alert("UserInfo: gotDelete: " + text);
    location.reload();
}       // gotDelete

/************************************************************************
 *  noDelete                                                            *
 *                                                                      *
 *  This method is called if there is no script to delete the Blog.     *
 ************************************************************************/
function noDelete()
{
    alert("UserInfo: script deleteBlogXml.php not found on server");
}       // noDelete

/************************************************************************
 *  replyBlog                                                           *
 *                                                                      *
 *  This method is called when the user requests to view the reply      *
 *  to a specific queued message.                                       *
 *  This is the onclick method of <button id='Reply...'>                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Reply...'>                              *
 ************************************************************************/
function replyBlog()
{
    let blid        = this.id.substring(5);
    let message     = this.form.elements['message' + blid].value;

    // get the subdistrict information file
    let parms       = {'id'         : blid,
                       'message'    : message};

    HTTP.post("replyBlogXml.php",
              parms,
              gotReply,
              noReply);
    return true;
}   // replyBlog

/************************************************************************
 *  gotReply                                                            *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the act of replying to the blog is received.                        *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc  XML response file describing the sending of the reply   *
 ************************************************************************/
function gotReply(xmlDoc)
{
    let root        = xmlDoc.documentElement;
    let text        = '';
    let msgs        = root.getElementsByTagName('msg');
    for (let item of msgs)
    {
        text        += item.textContent;
    }
    alert("UserInfo: gotReply: " + text);
    location.reload();
}       // gotReply

/************************************************************************
 *  noReply                                                             *
 *                                                                      *
 *  This method is called if there is no script to reply to the Blog.   *
 ************************************************************************/
function noReply()
{
    alert("UserInfo: script replyBlogXml.php not found on server");
}       // function noReply
