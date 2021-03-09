/************************************************************************
 *  contactAuthor.js                                                    *
 *                                                                      *
 *  Dynamic functionality of contactAuthor.php                          *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/30      created                                         *
 *      2015/05/14      if invoked in a half frame, close the frame     *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2020/01/04      add cancel button                               *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

window.onload   = onLoad;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  The onload method of the web page.  This is invoked after the       *
 *  web page has been loaded into the browser.                          *
 ************************************************************************/
function onLoad()
{
    let trace   = '';
    // activate functionality for individual input elements
    for(let i = 0; i < document.forms.length; i++)
    {               // loop through all forms
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {           // loop through all elements
            let element     = form.elements[j];
            let name        = element.name;
            if (!name || name.length == 0)
                name        = element.id;
            trace           += name + ", ";
            // identify change action for each cell
            switch(name.toLowerCase())
            {       // switch on column name
                case 'message':
                {
                    element.focus();
                    element.setSelectionRange(element.value.length,
                                  element.value.length);
                    break;
                }

                case 'blog':
                {   // action button
                    trace   += "onclick=postBlog, ";
                    element.onclick     = postBlog;
                    break;
                }   // action button

                case 'cancel':
                {   // action button
                    element.onclick     = cancel;
                    break;
                }   // action button

            }       // switch on column name
        }           // loop through all elements
    }               // loop through all forms
}       // function onLoad

/************************************************************************
 *  function postBlog                                                   *
 *                                                                      *
 *  This function is called when the user clicks on the "Blog" button.  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Blog'> element                      *
 *      e               instance of Event                               *
 ************************************************************************/
function postBlog(e)
{
    let form    = this.form;
    let parms   = {'id'         : form.id.value,
                   'tablename'  : form.tablename.value,
                   'message'    : form.message.value,
                   'email'      : form.email.value};

    // post the blog
    HTTP.post("postBlogXml.php",
                parms,
                gotPosted,
                noPosted);
}       // postBlog

/************************************************************************
 *  function gotPosted                                                  *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the completion of the post is retrieved from the server.            *
 ************************************************************************/
function gotPosted(xmlDoc)
{
    let root    = xmlDoc.documentElement;
    if (root)
    {           // have XML response
        let msg = "";
        for(let i = 0; i < root.childNodes.length; i++)
        {       // loop through children
            let node    = root.childNodes[i];
            if (node.nodeName && node.nodeName == 'msg')
                msg     += node.textContent;
        }       // loop through children
        if (msg.length > 0)
            alert("gotPosted: " + msg);
        if (window.frameElement)
            closeFrame();
        else
            location    = 'FamilyTree/nominalIndex.php';
    }           // have XML response
    else
        alert("contactAuthor.js: gotPosted: typeof(xmlDoc)=" + typeof(xmlDoc) + ", xmlDoc=" +
                xmlDoc);
}       // gotPosted

/************************************************************************
 *  function noPosted                                                   *
 *                                                                      *
 *  This method is called if there is no action script on the server.   *
 ************************************************************************/
function noPosted()
{
    alert("contactAuthor: unable to find script postBlogXml.php on server");
}       // function noPosted

/************************************************************************
 *  function cancel                                                     *
 *                                                                      *
 *  This function is called when the user clicks on the "Cancel"        *
 *  button.                                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Cancel'> element                    *
 *      e               instance of Event                               *
 ************************************************************************/
function cancel(e)
{
    closeFrame();
}       // function cancel

