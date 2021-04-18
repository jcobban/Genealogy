/************************************************************************
 *  UserInfo.js                                                         *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page UserInfo.php.                                                  *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/30      created                                         *
 *      2014/07/25      fix comments                                    *
 *                      store user information in a cookie              *
 *      2014/08/22      delete the user cookie if there are any errors  *
 *      2014/08/23      setting of cookie for user information          *
 *                      moved to Signon.php                             *
 *      2019/02/10      no longer need to call pageInit                 *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.    *
 *                                                                      *
 *  Define the function to be called once the web page is loaded.       *
 ************************************************************************/
    window.onload   = onLoad;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization functions once the page is loaded.           *
 ************************************************************************/
function onLoad()
{

    // refresh the page that the user signed on from
    var invoker             = window.opener;
    if (invoker)
        invoker.location    = invoker.location;

    // activate dynamic functionality of all forms
    for (var fi = 0; fi < document.forms.length; fi++)
    {           // loop through all forms
        var form            = document.forms[fi];
    
        // activate handling of key strokes in text input fields
        // including support for context specific help
        var formElts        = form.elements;
        for (var i = 0; i < formElts.length; ++i)
        {       // loop through elements
            var element     = formElts[i];
    
            // take action on specific elements by name
            var name        = element.name;
            if (name.length == 0)
                name        = element.id;
    
            if (name.substring(0, 6) == 'Delete')
                element.onclick = deleteBlog;
            else
            if (name.substring(0, 5) == 'Reply')
                element.onclick = replyBlog;
        }       // looping through elements
    }           // loop through all forms
}           // onLoad

/************************************************************************
 *  function deleteBlog                                                 *
 *                                                                      *
 *  This method is called when the user requests to delete a specific   *
 *  message. This is the onclick method of <button id='Delete...'>      *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Delete...'>                             *
 ************************************************************************/
function deleteBlog()
{
    var form    = this.form;
    var blid    = this.id.substring(6);
    // get the subdistrict information file
    parms   = {'id'     : blid};

    HTTP.post("deleteBlogXml.php",
        parms,
        gotDelete,
        noDelete);
    return true;
}   // deleteDelete

/************************************************************************
 *  function gotDelete                                                  *
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
    //alert("UserInfo: gotDelete: " + new XMLSerializer().serializeToString(xmlDoc));
    location.reload();
}       // gotDelete

/************************************************************************
 *  function noDelete                                                   *
 *                                                                      *
 *  This method is called if there is no script to delete the Blog.     *
 ************************************************************************/
function noDelete()
{
    alert("UserInfo: script deleteBlogXml.php not found on server");
}       // noDelete

/************************************************************************
 *  function replyBlog                                                  *
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
    var form    = this.form;
    var blid    = this.id.substring(5);
    var message = this.form.elements['message' + blid].value;

    // get the subdistrict information file
    parms   = {'id'         : blid,
               'message'    : message};

    HTTP.post("replyBlogXml.php",
              parms,
              gotReply,
              noReply);
    return true;
}   // replyBlog

/************************************************************************
 *  function gotReply                                                   *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the act of replying to the blog is received.                        *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc  XML response file describing the sending of the reply   *
 ************************************************************************/
function gotReply(xmlDoc)
{
    //alert("UserInfo: gotReply: " + new XMLSerializer().serializeToString(xmlDoc));
    location.reload();
}       // gotReply

/************************************************************************
 *  function noReply                                                    *
 *                                                                      *
 *  This method is called if there is no script on the server           *
 *  to reply to the Blog.                                               *
 ************************************************************************/
function noReply()
{
    alert("UserInfo: script replyBlogXml.php not found on server");
}       // noReply
