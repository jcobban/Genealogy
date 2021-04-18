/************************************************************************
 *  BlogPost.js                                                         *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page BlogPost.php.                                                  *
 *                                                                      *
 *  History:                                                            *
 *      2018/09/12      created                                         *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/04/13      support new tinyMCE                             *
 *      2019/08/27      add Close button                                *
 *      2020/06/03      hide right column                               *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *      2021/04/04      use ES2015 import                               *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {actMouseOverHelp, hideRightColumn, openFrame, openSignon,
        popupAlert, args, iframe}
            from "../jscripts6/util.js";
import "../jscripts6/CommonForm.js";
/* global tinyMCE */

/************************************************************************
 *  Initialization code that is executed when this script is loaded.    *
 *                                                                      *
 *  Define the function to be called once the web page is loaded.       *
 ************************************************************************/
window.addEventListener("load", onLoad);

var lang                    = 'en';
var blogid                  = 0;
var edit                    = 'N';
var debug                   = false;

for(const key in args)
{                       // loop through parameters
    switch (key.toLowerCase())
    {                   // act on specific parameters
        case 'lang':
            lang                    = args[key];
            break;

        case 'blogid':
            blogid                  = args[key];
            break;

        case 'edit':
            edit                    = args[key].toUpperCase();
            break;

        case 'debug':
            if (args[key].toLowerCase() == 'y')
                debug               = true;
            break;

    }                   // act on specific parameters
}                       // loop through parameters

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization functions once the page is loaded.           *
 ************************************************************************/
function onLoad()
{
    document.body.onresize  = onWindowResize;

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(let i = 0; i < document.forms.length; i++)
    {
        let form            = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {
            let element     = form.elements[j];

            let name        = element.name;
            if (name.length == 0)
            {           // button elements usually have id not name
                name        = element.id;
            }           // button elements usually have id not name
            let result      = /^([a-zA-Z]*)([0-9]*)$/.exec(name);
            if (result !== null)
            {
                name        = result[1];
                //id          = result[2];
            }

            // take action specific to the element based on its name
            switch(name.toLowerCase())
            {       // switch on name
                case 'subject':
                {
                    if (blogid == 0)
                        element.focus();
                    break;
                }

                case 'message':
                {   // blog text area
                    let msgLabel    = document.getElementById('msgLabel');
                    let mframe      = tinyMCE.DOM.get('message_ifr');
                    let textwidth   = window.innerWidth -
                                      msgLabel.offsetWidth - 40;
                    tinyMCE.DOM.setStyle(mframe, 'width', textwidth + 'px');
                    if (blogid > 0)
                    {               // focus on the current input element
                        tinyMCE.get('message').focus();
                    }               // focus on the current input element
                    break;
                }   // blog text area

                case 'postblog':
                {   // post blog button
                    element.addEventListener('click', postBlog);
                    break;
                }   // post blog button

                case 'edit':
                {
                    element.addEventListener('click', editBlog);
                    break;
                }

                case 'del':
                {
                    element.addEventListener('click', delBlog);
                    break;
                }

                case 'close':
                {
                    element.addEventListener('click', close);
                    break;
                }

                default:
                {
                    break;
                }

            }       // switch on name
        }   // loop through elements in form
    }       // iterate through all forms

    // pop up help balloon if the mouse hovers over the message input field
    // for more than 2 seconds
    let element         = document.getElementById('message_ifr');
    if (element)
    {
        actMouseOverHelp.call(element, 'message');
    }

    hideRightColumn();
}       // function onLoad

/************************************************************************
 *  function onWindowResize                                             *
 *                                                                      *
 *  This method is called when the browser window size is changed.      *
 *  For example if the window is split between the main display and     *
 *  a second display, resize.                                           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <body> element                                      *
 ************************************************************************/
function onWindowResize()
{
    if (iframe)
        openFrame(iframe.name, null, "right");
    let msgLabel            = document.getElementById('msgLabel');
    let mframe              = tinyMCE.DOM.get('message_ifr');
    let textwidth           = window.innerWidth - msgLabel.offsetWidth - 40;
    tinyMCE.DOM.setStyle(mframe, 'width', textwidth + 'px');
    let subject             = document.getElementById('subject');
    subject.style.width     = textwidth + 'px';
    let email               = document.getElementById('emailAddress');
    email.style.width       = textwidth + 'px';
}       // function onWindowResize

/************************************************************************
 *  function postBlog                                                   *
 *                                                                      *
 *  This method is called when the user requests to post                *
 *  a message to the blog of an individual.                             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='PostBlog'>                              *
 *      ev          instance of click Event                             *
 ************************************************************************/
function postBlog(ev)
{
    ev.stopPropagation();
    let form                    = this.form;
    let userid                  = form.userid.value;
    let email                   = '';
    let subject                 = form.subject.value;
    if (form.emailAddress)
        email                   = form.emailAddress.value;

    if (userid == '' && email == '')
    {                       // not signed on or identified
        openSignon();       // require user to sign on
    }                       // not signed on or identified
    else
    {                       // identified
        let idir                = form.blogid.value;
        let message             = tinyMCE.get('message').getContent();
        let parms               = { "idir"          : idir,
                                    "table"         : 'Blogs',
                                    "emailAddress"  : email,
                                    "subject"       : subject,
                                    "message"       : message};
        if (edit == 'Y')
        {
            parms['update'] = 'Y';
        }
        if (debug)
        {
            let blogParms       = JSON.stringify(parms);
            alert("BlogPost.js: postBlog: " + blogParms);
            parms['debug']      = 'y';
        }

        // invoke script to update Event and return XML result
        HTTP.post('/postBlogXml.php',
                  parms,
                  gotBlog,
                  noBlog);
    }                       // identified
}       // function postBlog

/************************************************************************
 *  function gotBlog                                                    *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  a posted blog is retrieved from the database.                       *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc          response from web server as XML document        *
 ************************************************************************/
function gotBlog(xmlDoc)
{
    let root                    = xmlDoc.documentElement;
    let messageElt              = document.getElementById('PostBlog');
    let msg                     = "";

    if (debug.toLowerCase() == 'y')
    {
        alert("BlogPost.js: gotBlog: xmlDoc=" +
              new XMLSerializer().serializeToString(xmlDoc));
    }

    if (root && root.nodeName == 'blog')
    {
        for(let i = 0; i < root.childNodes.length; i++)
        {                   // loop through children
            let node            = root.childNodes[i];
            if (node.nodeName == 'msg')
                msg             += node.textContent;
        }                   // loop through children
    }
    else
    {                       // error
        if (root)
        {
            for(let i = 0; i < root.childNodes.length; i++)
            {               // loop through children
                let node        = root.childNodes[i];
                if (node.nodeValue != null)
                    msg += node.nodeValue;
            }               // loop through children
        }
        else
            msg                 += root;
    }                       // error

    if (msg.length > 0)
        popupAlert(msg, messageElt);

    let url                     = location.href;
    url                         = url.replace(/&edit=Y/i, '');
    location                    = url;  // refresh the page
}       // function gotBlog

/************************************************************************
 *  function noBlog                                                     *
 *                                                                      *
 *  This method is called if there is no blog script on the web server. *
 ************************************************************************/
function noBlog()
{
    let messageElt              = document.getElementById('PostBlog');
    popupAlert('BlogPost.js: noBlog: ' +
                    'script "postBlogXml.php" not found on web server',
               messageElt);
    location.reload(); // refresh the page
}       // function noBlog

/************************************************************************
 *  function editBlog                                                   *
 *                                                                      *
 *  This method is called if the user requests to edit the blog         *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='edit...'>                               *
 *      e           instance of click Event                             *
 ************************************************************************/
function editBlog(e)
{
    e.stopPropagation();

    location    = "/BlogPost.php?blogid=" + this.id.substring(4) +
                        "&table=Blogs&lang=" + lang + "&edit=Y";
    return false;
}       // function editBlog

/************************************************************************
 *  function delBlog                                                    *
 *                                                                      *
 *  This method is called if the user requests to delete the blog       *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='del...'>                                *
 *      ev          instance of click Event                             *
 ************************************************************************/
function delBlog(ev)
{
    ev.stopPropagation();

    let blid        = this.id.substring(3);

    let parms       = {"blid"   : blid};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
              parms,
              gotBlog,
              noDelBlog);
}       // function delBlog

/************************************************************************
 *  function noDelBlog                                                  *
 *                                                                      *
 *  This method is called if there is no blog script on the web server. *
 ************************************************************************/
function noDelBlog()
{
    alert('BlogPost.js: noDelBlog: ' +
                'script "deleteBlogXml.php" not found on web server');
}       // function noDelBlog

/************************************************************************
 *  function close                                                      *
 *                                                                      *
 *  This method is called if the user requests to edit the blog         *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='close'>                                 *
 *      e           instance of click Event                             *
 ************************************************************************/
function close(e)
{
    e.stopPropagation();

    location        = "/Blogs.php";
    return false;
}       // function close

