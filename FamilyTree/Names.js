/************************************************************************
 *  Names.js                                                            *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Names.php.														*
 *                                                                      *
 *  History:															*
 *      2011/10/31      created                                         *
 *      2012/01/13      change class names                              *
 *      2013/05/17      do not emit alert for missing table, it may just*
 *                      mean no names matched                           *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2015/05/18      add ability to edit the Surname record          *
 *      2015/06/02      use main style for TinyMCE editor               *
 *      2017/10/13      add support to validate regular expression      *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/03/12      only include actual error message in reporting  *
 *                      bad pattern                                     *
 *      2020/04/25      support namesTable <div> display=grid           *
 *                      use JSON to validate SQL regular expression     *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize elements.												*
 *                                                                      *
 *  Input:                                                              *
 *      this        Window                                              *
 *      ev          Javascript Event object                             *
 ************************************************************************/
function onLoad(ev)
{
    // activate functionality of form elements
    for (var fi = 0; fi < document.forms.length; fi++)
    {			                    // loop through all forms
        var  form                       = document.forms[fi];
        var formElts                    = form.elements;
        for (var i = 0; i < formElts.length; ++i)
        {		                    // loop through all elements in the form
            var element  = formElts[i];

            var  name                   = element.name;
            if (name.length == 0)
            {		                // button elements usually have id not name
                name                    = element.id;
            }		                // button elements usually have id not name

            switch(name)
            {		                // act on specific element
                case 'PostBlog':
                {	                // post blog button
                    element.onclick     = postBlog;
                    break;
                }	                // post blog button

                case 'Pattern':
                {
                    element.onchange    = changePattern;
                    break;
                }	                // Pattern input field

                case 'message':
                {
                    var frame       = document.getElementById('message_ifr');
                    frame.helpDiv       = 'message';
                    actMouseOverHelp(frame);    // tinymce
                    break;
                }	                // Pattern input field

            }		                // act on specific element
        }		                    // loop through all elements in the form
    }			                    // loop through all forms

    // activate functionality associated with hyperlinks
    for(var il = 0; il < document.links.length; il++)
    {	                    // loop through all links
        var link                = document.links[il];
        actMouseOverHelp(link);
    }			            // loop through all links

    // activate functionality of table cells
    var  table      = document.getElementById('namesTable');
    if (table)
    {		                // table defined in page
        if (table.rows)
        {                   // IE  support
            for(var ir = 0; ir < table.rows.length; ir++)
            {		        // loop through all rows of table of names
                var  row        = table.rows[ir];
                for (var ic = 0; ic < row.cells.length; ic++)
                {	        // loop through all cells of table of names
                    var cell    = row.cells[ic];
                    if (cell)
                        cell.onclick  = followLink;
                }	        // loop through all cells of table of names
            }		        // loop through all rows of table of names
        }                   // IE support
        else
        if (table.children)
        {                   // W3C support
            for (var ic = 0; ic < table.children.length; ic++)
            {	            // loop through all childrenof the grid div
                var cell  = table.children[ic];
                if (cell)
                    cell.onclick  = followLink;
            }	            // loop through all children of the grid div
        }                   // W3C support
        else
            alert('Names.js: onLoad: namesTable=' + table);
    }		                // table defined in page
}		// function onLoad

/************************************************************************
 *  function followLink                                                 *
 *                                                                      *
 *  This is the onclick method for a table cell that contains a <a>		*
 *  element.															*
 *  When this cell is clicked on, it acts as if the mouse was clicking  *
 *  on the contained <a> tag.											*
 *                                                                      *
 *  Input:																*
 *      this        HtmlElement                                         *
 ************************************************************************/
function followLink()
{
    for(var ie = 0; ie < this.childNodes.length; ie++)
    {		                // loop through all children
        var node                = this.childNodes[ie];
        if (node.nodeName == 'A')
        {	                // anchor node
            location            = node.href;
            return false;
        }	                // anchor node
    }		                // loop through all children
    return false;
}		// function followLink

/************************************************************************
 *  function postBlog                                                   *
 *                                                                      *
 *  This method is called when the user requests to post                *
 *  a message to the blog of an individual.								*
 *                                                                      *
 *  Input:																*
 *      this        <button id='PostBlog'>                              *
 ************************************************************************/
function postBlog(rownum)
{
    var  form               = this.form;
    var  userid             = form.userid.value;
    var  email              = '';
    if (form.emailAddress)
        email               = form.emailAddress.value;

    if (userid == '' && email == '')
    {			// not signed on or identified
        openSignon();
    }			// not signed on or identified
    else
    {
        var idnr            = form.idnr.value;
        var message         = tinyMCE.get('message').getContent();
        var parms           = { "idnr"		: idnr,
                                "email"		: email,
                                "message"	: message};

        if (debug.toLowerCase() == 'y')
        {
            alert("Names.js: postBlog: /postBlogXml?idnr=" + idnr +
                    "&email=" + email +
                    "&message=" + message);
        }
        // invoke script to update Event and return XML result
        HTTP.post('/postBlogXml.php',
                  parms,
                  gotBlog,
                  noBlog);
    }
}		// postBlog

/************************************************************************
 *  function gotBlog                                                    *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  a posted blog is retrieved from the database.						*
 *                                                                      *
 *  Input:																*
 *      xmlDoc      response from web server as XML document            *
 ************************************************************************/
function gotBlog(xmlDoc)
{
    var  evtForm            = document.evtForm;
    var  root               = xmlDoc.documentElement;
    var  messageElt         = document.getElementById('PostBlog');
    var  msg                = "";

    if (root && root.nodeName == 'blog')
    {
        for(var i = 0; i < root.childNodes.length; i++)
        {		// loop through children
            var node        = root.childNodes[i];
            if (node.nodeName == 'msg')
                msg         += node.textContent;
        }		// loop through children
    }
    else
    {		// error
        if (root)
        {
            for(var i = 0; i < root.childNodes.length; i++)
            {		// loop through children
                var node    = root.childNodes[i];
                if (node.nodeValue != null)
                    msg     += node.nodeValue;
            }		// loop through children
        }
        else
            msg  += root;
    }		// error

    if (msg.length > 0)
        popupAlert(msg, messageElt);
 
    location  = location;
}		// function gotBlog

/************************************************************************
 *  function noBlog                                                     *
 *                                                                      *
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noBlog()
{
    alert('Names.js: noBlog: ' +
                'script "postBlogXml.php" not found on web server');
}		// function noBlog

/************************************************************************
 *  function noDelBlog                                                  *
 *                                                                      *
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noDelBlog()
{
    alert('Names.js: noDelBlog: ' +
                'script "deleteBlogXml.php" not found on web server');
}		// function noDelBlog

/************************************************************************
 *  function changePattern                                              *
 *                                                                      *
 *  This method is called if the user changes the regular expression    *
 *  pattern.															*
 *                                                                      *
 *  Input:																*
 *      this        <button id='Pattern'>                                *
 ************************************************************************/
function changePattern()
{
    var  pattern      = this.value;
    if (pattern.length > 0)
    {		// invoke script to validate regular expression pattern
        var options             = {"timeout"    : false};
        var url     = '/validateSqlRegexpPatternJSON.php?pattern=' + pattern;
        HTTP.get(url,
                 gotTestPattern,
                 options);
    }		// invoke script to validate regular expression pattern
}		// function changePattern

/************************************************************************
 *  function gotTestPattern                                             *
 *                                                                      *
 *  This method is called to process the response to testing the        *
 *  name pattern against the SQL server implementation.                 *
 *																		*
 *  Parameters:															*
 *		obj		    Javascript object returned from server				*
 ************************************************************************/
function gotTestPattern(obj)
{
    if (obj === null)
    {
        alert('Names.js:gotTestPattern: error response object is null');
        return;
    }
    if (obj.msg)
    {
        let pattern     = obj.parms.pattern;
        let message     = document.getElementById('invalidPattern').innerHTML;
        message         = message.replace('$pattern', pattern);
        popupAlert(message + obj.msg,
                    document.getElementById('Pattern'));
    }
}		// function gotTestPattern

/************************************************************************
 *  function editBlog                                                   *
 *                                                                      *
 *  This method is called if the user requests to edit the blog         *
 *  message.															*
 *                                                                      *
 *  Input:																*
 *      thi         <button id='blEdit'>                                *
 ************************************************************************/
function editBlog()
{
    alert('to do: editBlog: ' + this.id.substring(6));
    return false;
}		// function editBlog

/************************************************************************
 *  function delBlog                                                    *
 *                                                                      *
 *  This method is called if the user requests to delete the blog       *
 *  message.															*
 *                                                                      *
 *  Input:																*
 *      this        <button id='blDel'>                                 *
 ************************************************************************/
function delBlog()
{
    var  form       = this.form;
    var  blid       = this.id.substring(5);

    var parms       = {"blid"	: blid};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
              parms,
              gotBlog,
              noDelBlog);
}		// function delBlog
