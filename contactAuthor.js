/************************************************************************
 *  contactAuthor.js													*
 *																		*
 *  Dynamic functionality of contactAuthor.php							*
 *																		*
 *  History:															*
 *		2014/03/30		created											*
 *		2015/05/14		if invoked in a half frame, close the frame		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  The onload method of the web page.  This is invoked after the		*
 *  web page has been loaded into the browser. 							*
 ************************************************************************/
function onLoad()
{
    // perform common page initialization
    pageInit();

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {			// loop through all forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var	element		= form.elements[j];
		    var name		= element.name;
		    if (!name || name.length == 0)
				name		= element.id;

		    // pop up help balloon if the mouse hovers over a element
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    // identify change action for each cell
		    switch(name)
		    {		// switch on column name
				case 'message':
				{
				    element.focus();
				    element.setSelectionRange(element.value.length,
							      element.value.length);
				    break;
				}
		
				case 'Blog':
				{	// action button
				    element.onclick	= postBlog;
				    break;
				}	// action button

		    }		// switch on column name
		}		// loop through all elements
    }			// loop through all forms
}		// onLoad

/************************************************************************
 *  function postBlog													*
 *																		*
 *  This function is called when the user clicks on the "Blog" button.	*
 *																		*
 *  Input:																*
 *		this				<button id='Blog'> element					*
 ************************************************************************/
function postBlog()
{
    var	form	= this.form;
    var	parms	= {'id'		: form.id.value,
				   'tablename'	: form.tablename.value,
				   'message'	: form.message.value,
				   'email'	: form.email.value};

    // post the blog
    HTTP.post("postBlogXml.php",
				parms,
				gotPosted,
				noPosted);
}		// postBlog

/************************************************************************
 *  function gotPosted													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the completion of the post is retrieved from the server.			*
 ************************************************************************/
function gotPosted(xmlDoc)
{
    var	root	= xmlDoc.documentElement;
    if (root)
    {			// have XML response
		var	msg	= "";
		for(var i = 0; i < root.childNodes.length; i++)
		{		// loop through children
		    var node	= root.childNodes[i];
		    if (node.nodeName && node.nodeName == 'msg')
				msg		+= node.textContent;
		}		// loop through children
		if (msg.length > 0)
		    alert("gotPosted: " + msg);
		if (window.frameElement)
		    closeFrame();
		else
		    location	= 'FamilyTree/nominalIndex.php';
    }			// have XML response
    else
		alert("contactAuthor.js: gotPosted: typeof(xmlDoc)=" + typeof(xmlDoc) + ", xmlDoc=" +
				xmlDoc);
}		// gotPosted

/************************************************************************
 *  function noPosted													*
 *																		*
 *  This method is called if there is no action script on the server.	*
 ************************************************************************/
function noPosted()
{
    alert("contactAuthor: unable to find script postBlogXml.php on server");
}		// function noPosted

