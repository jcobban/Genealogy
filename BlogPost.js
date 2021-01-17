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
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.    *
 *                                                                      *
 *  Define the function to be called once the web page is loaded.       *
 ************************************************************************/
window.onload               = onLoad;

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

    var names               = "";

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
        var form            = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {
            var element     = form.elements[j];

            var name        = element.name;
            var id          = '';
            if (name.length == 0)
            {           // button elements usually have id not name
                name        = element.id;
            }           // button elements usually have id not name
            var result      = /^([a-zA-Z]*)([0-9]*)$/.exec(name);
            if (result !== null)
            {
                name        = result[1];
                id          = result[2];
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
                    var msgLabel    = document.getElementById('msgLabel');
                    var mframe      = tinymce.DOM.get('message_ifr');
                    var textwidth   = window.innerWidth -
                                      msgLabel.offsetWidth - 40;
                    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
                    if (blogid > 0)
                    {               // focus on the current input element
                        tinymce.get('message').focus();
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
    element             = document.getElementById('message_ifr');
    if (element)
    {
        element.helpDiv = 'message';
        actMouseOverHelp(element);
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
    var msgLabel            = document.getElementById('msgLabel');
    var mframe              = tinymce.DOM.get('message_ifr');
    var textwidth           = window.innerWidth - msgLabel.offsetWidth - 40;
    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
    var subject             = document.getElementById('subject');
    subject.style.width     = textwidth + 'px';
    var email               = document.getElementById('emailAddress');
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
 *      e           instance of click Event                             *
 ************************************************************************/
function postBlog(e)
{
    var form                    = this.form;
    var userid                  = form.userid.value;
    var email                   = '';
    var subject                 = form.subject.value;
    if (form.emailAddress)
        email                   = form.emailAddress.value;

    if (userid == '' && email == '')
    {                       // not signed on or identified
        openSignon();       // require user to sign on
    }                       // not signed on or identified
    else
    {                       // identified
        var idir                = form.blogid.value;
        var message             = tinyMCE.get('message').getContent();
        var parms               = { "idir"          : idir,
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
            var blogParms       = JSON.stringify(parms);
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
    var evtForm                 = document.evtForm;
    var root                    = xmlDoc.documentElement;
    var messageElt              = document.getElementById('PostBlog');
    var msg                     = "";

    if (debug.toLowerCase() == 'y')
    {
        alert("BlogPost.js: gotBlog: xmlDoc=" +
              new XMLSerializer().serializeToString(xmlDoc));
    }

    if (root && root.nodeName == 'blog')
    {
        for(var i = 0; i < root.childNodes.length; i++)
        {                   // loop through children
            var node            = root.childNodes[i];
            if (node.nodeName == 'msg')
                msg             += node.textContent;
        }                   // loop through children
    }
    else
    {                       // error
        if (root)
        {
            for(var i = 0; i < root.childNodes.length; i++)
            {               // loop through children
                var node        = root.childNodes[i];
                if (node.nodeValue != null)
                    msg += node.nodeValue;
            }               // loop through children
        }
        else
            msg                 += root;
    }                       // error

    if (msg.length > 0)
        popupAlert(msg, messageElt);

    var url                     = location.href;
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
    var messageElt              = document.getElementById('PostBlog');
    popupAlert('BlogPost.js: noBlog: ' +
                    'script "postBlogXml.php" not found on web server',
               messageElt);
    location                    = location; // refresh the page
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
 *      e           instance of click Event                             *
 ************************************************************************/
function delBlog(e)
{
    e.stopPropagation();

    var form        = this.form;
    var blid        = this.id.substring(3);

    var parms       = {"blid"   : blid};

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

