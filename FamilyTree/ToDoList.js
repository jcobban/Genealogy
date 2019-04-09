/************************************************************************
 *  ToDoList.js							                            	*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page ToDoList.php.								                    *
 *																		*
 *  History:								                            *
 *	    2010/12/25	    created						                    *
 *	    2012/01/13	    change class names						        *
 *	    2013/08/01	    defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *                                                                      *
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  function onLoad								                        *
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 ************************************************************************/
function onLoad()
{
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		var projectId	= form.name.substring(8);

		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    // take action specific to element
		    var	name;
		    if (element.name && element.name.length > 0)
				name	= element.name;
		    else
				name	= element.id;

		    if (name.substring(0,7) == 'message')
				element.onfocus	= messageFocus;
		    else
		    if (name.substring(0,8) == 'PostBlog')
				element.onclick	= postBlog;
		}	// loop through all elements in form
    }		// loop through all forms in this page
}		// onLoad

/************************************************************************
 *  function messageFocus								*
 *																		*
 *  This method is called when the user tabs into or clicks								*
 *  on a message text area.								*
 *																		*
 *  Input:								*
 *	this points at the <textarea> element								*
 ************************************************************************/
function messageFocus()
{
    this.onfocus	= null;
    this.select();
    this.onfocus	= messageFocus;
}

/************************************************************************
 *  function postBlog								*
 *																		*
 *  This method is called when the user requests to post a blog								*
 *  for a project.								*
 *																		*
 *  Input:								*
 *	this points at the PostBlog <input type='button'> element								*
 ************************************************************************/
function postBlog()
{
    var	blogform	= this.form;
    var projectId	= blogform.name.substring(8);
    var message		= blogform['message' + projectId].value;

    var parms		= {
				"projectId"	: projectId,
				"message"	: message};

    // invoke script to update Event and return XML result
    HTTP.post("postProjectBlog.php",
		      parms,
		      gotBlog,
		      noBlog);
}	// postBlog

/************************************************************************
 *  function gotBlog							                    	*
 *																		*
 *  This method is called when the XML file representing				*
 *  a posted blog is retrieved from the database.						*
 ************************************************************************/
function gotBlog(xmlDoc)
{
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName == 'blog')
    {
		//alert("gotBlog: " + tagToString(root));
		location	= location;	// force refresh of window
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root)
		{
		    for(var i = 0; i < root.childNodes.length; i++)
		    {		// loop through children
				var node	= root.childNodes[i];
				if (node.nodeValue != null)
				    msg	+= node.nodeValue;
		    }		// loop through children
		}
		else
		    msg	+= root;
		alert (msg);
    }		// error
}		// gotBlog

/************************************************************************
 *  function noBlog							                        	*
 *																		*
 *  This method is called if there is no blog							*
 *  file.								                                *
 ************************************************************************/
function noBlog()
{
    alert('No response file from postProjectBlog.php');
}		// noBlog
